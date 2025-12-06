<?php

declare(strict_types=1);

use App\Domain\Property\Enums\PropertyStatus;
use App\Domain\Property\Models\Property;
use App\Domain\Property\Models\PropertyImage;
use App\Domain\Property\Repositories\PropertyImageRepositoryInterface;
use App\Domain\Property\Repositories\PropertyRepositoryInterface;
use App\Domain\Property\Services\PropertyService;
use App\Domain\Shared\Exceptions\DomainException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

function makeProperty(array $attrs = []): Property
{
    $p = new Property;
    foreach ($attrs as $k => $v) {
        $p->{$k} = $v;
    }

    return $p;
}

function makeImage(array $attrs = []): PropertyImage
{
    $p = new PropertyImage;
    foreach ($attrs as $k => $v) {
        $p->{$k} = $v;
    }

    return $p;
}

beforeEach(function () {
    $this->propertyRepo = Mockery::mock(PropertyRepositoryInterface::class);
    $this->imageRepo = Mockery::mock(PropertyImageRepositoryInterface::class);
    $this->service = new PropertyService($this->propertyRepo, $this->imageRepo);
});

afterEach(function () {
    Mockery::close();
});

// ────────────────────────────────────────────────────────────────
// Property CRUD
// ────────────────────────────────────────────────────────────────

describe('Property CRUD', function () {

    it('creates a property with auto-generated external_id when missing', function () {
        $data = ['category_id' => 1, 'price' => 1500];

        $this->propertyRepo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $d) {
                return str_starts_with($d['external_id'], 'PROP-')
                    && $d['status'] === 'DRAFT';
            })
            ->andReturn(makeProperty(['id' => 1]));

        $this->propertyRepo->shouldReceive('findWithRelations')
            ->with(1)
            ->once()
            ->andReturn(makeProperty(['id' => 1]));

        $result = $this->service->createProperty($data);

        expect($result)->toBeInstanceOf(Property::class);
    });

    it('preserves provided external_id', function () {
        $data = ['external_id' => 'MY-ID', 'category_id' => 1];

        $this->propertyRepo->shouldReceive('create')
            ->withArgs(fn (array $d) => $d['external_id'] === 'MY-ID')
            ->andReturn(makeProperty(['id' => 2]));

        $this->propertyRepo->shouldReceive('findWithRelations')
            ->andReturn(makeProperty(['id' => 2]));

        $result = $this->service->createProperty($data);
        expect($result->id)->toBe(2);
    });

    it('syncs amenities when provided', function () {
        $data = ['category_id' => 1, 'amenities' => [1, 2, 3]];

        $this->propertyRepo->shouldReceive('create')
            ->andReturn(makeProperty(['id' => 3]));

        $this->propertyRepo->shouldReceive('syncAmenities')
            ->with(3, [1, 2, 3])
            ->once();

        $this->propertyRepo->shouldReceive('findWithRelations')
            ->andReturn(makeProperty(['id' => 3]));

        $this->service->createProperty($data);
    });

    it('updates a property', function () {
        $this->propertyRepo->shouldReceive('update')
            ->with(1, ['price' => 2000])
            ->once()
            ->andReturn(makeProperty(['id' => 1, 'price' => 2000]));

        $this->propertyRepo->shouldReceive('findWithRelations')
            ->with(1)
            ->andReturn(makeProperty(['id' => 1, 'price' => 2000]));

        $result = $this->service->updateProperty(1, ['price' => 2000]);

        expect($result)->toBeInstanceOf(Property::class);
    });

    it('deletes a property', function () {
        $this->propertyRepo->shouldReceive('delete')
            ->with(1)
            ->once()
            ->andReturn(true);

        expect($this->service->deleteProperty(1))->toBeTrue();
    });
});

// ────────────────────────────────────────────────────────────────
// Status Workflow
// ────────────────────────────────────────────────────────────────

describe('Status Workflow', function () {

    it('submits a draft for approval', function () {
        $property = makeProperty(['id' => 1, 'status' => PropertyStatus::DRAFT]);

        $this->propertyRepo->shouldReceive('findByIdOrFail')
            ->with(1)
            ->andReturn($property);

        $this->propertyRepo->shouldReceive('updateStatus')
            ->with(1, PropertyStatus::PENDING_APPROVAL)
            ->andReturn(makeProperty(['id' => 1, 'status' => PropertyStatus::PENDING_APPROVAL]));

        $this->propertyRepo->shouldReceive('findWithRelations')
            ->andReturn(makeProperty(['id' => 1, 'status' => PropertyStatus::PENDING_APPROVAL]));

        $result = $this->service->submitForApproval(1);
        expect($result->status)->toBe(PropertyStatus::PENDING_APPROVAL);
    });

    it('approves a pending property', function () {
        $property = makeProperty(['id' => 1, 'status' => PropertyStatus::PENDING_APPROVAL]);

        $this->propertyRepo->shouldReceive('findByIdOrFail')
            ->andReturn($property);

        $this->propertyRepo->shouldReceive('updateStatus')
            ->with(1, PropertyStatus::APPROVED, 99)
            ->andReturn(makeProperty(['id' => 1, 'status' => PropertyStatus::APPROVED]));

        $this->propertyRepo->shouldReceive('findWithRelations')
            ->andReturn(makeProperty(['id' => 1, 'status' => PropertyStatus::APPROVED]));

        $result = $this->service->approve(1, 99);
        expect($result->status)->toBe(PropertyStatus::APPROVED);
    });

    it('rejects a pending property with reason', function () {
        $property = Mockery::mock(Property::class)->makePartial();
        $property->id = 1;
        $property->status = PropertyStatus::PENDING_APPROVAL;
        $property->shouldReceive('update')->with(['rejection_reason' => 'Bad photos'])->once();

        $this->propertyRepo->shouldReceive('findByIdOrFail')
            ->andReturn($property);

        $this->propertyRepo->shouldReceive('updateStatus')
            ->with(1, PropertyStatus::REJECTED, 99)
            ->andReturn(makeProperty(['id' => 1, 'status' => PropertyStatus::REJECTED]));

        $this->propertyRepo->shouldReceive('findWithRelations')
            ->andReturn(makeProperty(['id' => 1, 'status' => PropertyStatus::REJECTED]));

        $this->service->reject(1, 99, 'Bad photos');
    });

    it('publishes an approved property', function () {
        $property = makeProperty(['id' => 1, 'status' => PropertyStatus::APPROVED]);

        $this->propertyRepo->shouldReceive('findByIdOrFail')
            ->andReturn($property);

        $this->propertyRepo->shouldReceive('updateStatus')
            ->with(1, PropertyStatus::PUBLISHED)
            ->andReturn(makeProperty(['id' => 1, 'status' => PropertyStatus::PUBLISHED]));

        $this->propertyRepo->shouldReceive('findWithRelations')
            ->andReturn(makeProperty(['id' => 1, 'status' => PropertyStatus::PUBLISHED]));

        $result = $this->service->publish(1);
        expect($result->status)->toBe(PropertyStatus::PUBLISHED);
    });

    it('archives a published property', function () {
        $property = makeProperty(['id' => 1, 'status' => PropertyStatus::PUBLISHED]);

        $this->propertyRepo->shouldReceive('findByIdOrFail')
            ->andReturn($property);

        $this->propertyRepo->shouldReceive('updateStatus')
            ->with(1, PropertyStatus::ARCHIVED)
            ->andReturn(makeProperty(['id' => 1, 'status' => PropertyStatus::ARCHIVED]));

        $this->propertyRepo->shouldReceive('findWithRelations')
            ->andReturn(makeProperty(['id' => 1, 'status' => PropertyStatus::ARCHIVED]));

        $result = $this->service->archive(1);
        expect($result->status)->toBe(PropertyStatus::ARCHIVED);
    });

    it('throws if archiving an already archived property', function () {
        $property = makeProperty(['id' => 1, 'status' => PropertyStatus::ARCHIVED]);

        $this->propertyRepo->shouldReceive('findByIdOrFail')
            ->andReturn($property);

        $this->service->archive(1);
    })->throws(DomainException::class, 'Property is already archived.');

    it('throws on invalid status transition', function () {
        // DRAFT → PUBLISHED is not allowed
        $property = makeProperty(['id' => 1, 'status' => PropertyStatus::DRAFT]);

        $this->propertyRepo->shouldReceive('findByIdOrFail')
            ->andReturn($property);

        $this->service->publish(1);
    })->throws(DomainException::class);

    it('allows re-drafting a rejected property', function () {
        $property = makeProperty(['id' => 1, 'status' => PropertyStatus::REJECTED]);

        $this->propertyRepo->shouldReceive('findByIdOrFail')
            ->andReturn($property);

        $this->propertyRepo->shouldReceive('updateStatus')
            ->with(1, PropertyStatus::DRAFT, null)
            ->andReturn(makeProperty(['id' => 1, 'status' => PropertyStatus::DRAFT]));

        $this->propertyRepo->shouldReceive('findWithRelations')
            ->andReturn(makeProperty(['id' => 1, 'status' => PropertyStatus::DRAFT]));

        $result = $this->service->updateStatus(1, PropertyStatus::DRAFT);
        expect($result->status)->toBe(PropertyStatus::DRAFT);
    });
});

// ────────────────────────────────────────────────────────────────
// Image Management
// ────────────────────────────────────────────────────────────────

describe('Image Management', function () {

    it('adds an image to a property', function () {
        $this->propertyRepo->shouldReceive('findByIdOrFail')
            ->with(5)
            ->andReturn(makeProperty(['id' => 5]));

        $this->imageRepo->shouldReceive('countByProperty')
            ->with(5)
            ->andReturn(0);

        $this->imageRepo->shouldReceive('create')
            ->withArgs(fn (array $d) => $d['property_id'] === 5 && $d['is_primary'] === true)
            ->andReturn(makeImage(['id' => 1]));

        $result = $this->service->addImage(5, ['url' => 'https://example.com/img.jpg']);
        expect($result)->toBeInstanceOf(PropertyImage::class);
    });

    it('marks first image as primary', function () {
        $this->propertyRepo->shouldReceive('findByIdOrFail')
            ->andReturn(makeProperty(['id' => 1]));

        $this->imageRepo->shouldReceive('countByProperty')
            ->andReturn(0);

        $this->imageRepo->shouldReceive('create')
            ->withArgs(fn (array $d) => $d['is_primary'] === true)
            ->andReturn(makeImage(['id' => 1, 'is_primary' => true]));

        $result = $this->service->addImage(1, ['url' => 'https://example.com/1.jpg']);
        expect($result->is_primary)->toBeTrue();
    });

    it('clears existing primary when new image is set as primary', function () {
        $this->propertyRepo->shouldReceive('findByIdOrFail')
            ->andReturn(makeProperty(['id' => 1]));

        $this->imageRepo->shouldReceive('countByProperty')
            ->andReturn(3);

        $this->imageRepo->shouldReceive('setPrimary')
            ->with(1, 0)
            ->once();

        $this->imageRepo->shouldReceive('create')
            ->andReturn(makeImage(['id' => 4, 'is_primary' => true]));

        $this->service->addImage(1, ['url' => 'https://example.com/4.jpg', 'is_primary' => true]);
    });

    it('throws when exceeding 50 images limit', function () {
        $this->propertyRepo->shouldReceive('findByIdOrFail')
            ->andReturn(makeProperty(['id' => 1]));

        $this->imageRepo->shouldReceive('countByProperty')
            ->andReturn(50);

        $this->service->addImage(1, ['url' => 'https://example.com/51.jpg']);
    })->throws(DomainException::class, 'Maximum of 50 images per property exceeded.');

    it('deletes an image and re-assigns primary', function () {
        $image = Mockery::mock(PropertyImage::class)->makePartial();
        $image->is_primary = true;
        $image->shouldReceive('delete')->once();

        $this->imageRepo->shouldReceive('findByPropertyAndId')
            ->with(1, 10)
            ->andReturn($image);

        $newPrimary = Mockery::mock(PropertyImage::class)->makePartial();
        $newPrimary->shouldReceive('update')->with(['is_primary' => true])->once();

        $this->imageRepo->shouldReceive('findByProperty')
            ->with(1)
            ->andReturn(new EloquentCollection([$newPrimary]));

        expect($this->service->deleteImage(1, 10))->toBeTrue();
    });

    it('throws when deleting non-existent image', function () {
        $this->imageRepo->shouldReceive('findByPropertyAndId')
            ->with(1, 999)
            ->andReturn(null);

        $this->service->deleteImage(1, 999);
    })->throws(DomainException::class, 'Image not found for this property.');

    it('gets property images', function () {
        $this->imageRepo->shouldReceive('findByProperty')
            ->with(1)
            ->andReturn(new EloquentCollection([makeImage(), makeImage()]));

        $images = $this->service->getPropertyImages(1);
        expect($images)->toHaveCount(2);
    });
});

// ────────────────────────────────────────────────────────────────
// Read Methods
// ────────────────────────────────────────────────────────────────

describe('Read Methods', function () {

    it('returns null for non-existent property', function () {
        $this->propertyRepo->shouldReceive('findWithRelations')
            ->with(999)
            ->andReturn(null);

        expect($this->service->getPropertyById(999))->toBeNull();
    });

    it('returns null for non-published property via getPublishedPropertyById', function () {
        $property = makeProperty(['id' => 1, 'status' => PropertyStatus::DRAFT]);

        $this->propertyRepo->shouldReceive('findWithRelations')
            ->with(1)
            ->andReturn($property);

        expect($this->service->getPublishedPropertyById(1))->toBeNull();
    });

    it('returns published property via getPublishedPropertyById', function () {
        $property = makeProperty(['id' => 1, 'status' => PropertyStatus::PUBLISHED]);

        $this->propertyRepo->shouldReceive('findWithRelations')
            ->with(1)
            ->andReturn($property);

        $result = $this->service->getPublishedPropertyById(1);
        expect($result)->toBeInstanceOf(Property::class);
        expect($result->status)->toBe(PropertyStatus::PUBLISHED);
    });
});
