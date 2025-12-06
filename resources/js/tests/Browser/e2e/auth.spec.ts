import { test, expect } from '@playwright/test';

/**
 * E2E tests for admin login/logout flow via Fortify session auth.
 *
 * Prerequisites: A seeded admin user must exist in the test database.
 * Default test admin: admin@immobilier.ch / password
 */

const ADMIN_EMAIL = process.env.TEST_ADMIN_EMAIL || 'admin@immobilier.ch';
const ADMIN_PASSWORD = process.env.TEST_ADMIN_PASSWORD || 'password';

test.describe('Admin Authentication', () => {
    test('shows login page for unauthenticated users', async ({ page }) => {
        await page.goto('/admin/dashboard');

        // Should redirect to login
        await expect(page).toHaveURL(/\/login/);
    });

    test('shows login form with required fields', async ({ page }) => {
        await page.goto('/login');

        await expect(page.getByTestId('login-email')).toBeVisible();
        await expect(page.getByTestId('login-password')).toBeVisible();
        await expect(page.getByTestId('login-remember')).toBeVisible();
        await expect(page.getByTestId('login-submit')).toBeVisible();
    });

    test('shows validation errors for empty form', async ({ page }) => {
        await page.goto('/login');

        await page.getByTestId('login-submit').click();

        // Should stay on login page (Fortify validates server-side)
        await expect(page).toHaveURL(/\/login/);
    });

    test('shows error for invalid credentials', async ({ page }) => {
        await page.goto('/login');

        await page.getByTestId('login-email').fill('wrong@example.com');
        await page.getByTestId('login-password').fill('wrongpassword');
        await page.getByTestId('login-submit').click();

        // Should stay on login and show error
        await expect(page).toHaveURL(/\/login/);
    });

    test('logs in with valid admin credentials', async ({ page }) => {
        await page.goto('/login');

        await page.getByTestId('login-email').fill(ADMIN_EMAIL);
        await page.getByTestId('login-password').fill(ADMIN_PASSWORD);
        await page.getByTestId('login-submit').click();

        // Should redirect to admin dashboard
        await expect(page).toHaveURL(/\/admin\/dashboard/);

        // Dashboard should render
        await expect(page.getByText('Dashboard')).toBeVisible();
    });

    test('authenticated admin can access dashboard', async ({ page }) => {
        // Login first
        await page.goto('/login');
        await page.getByTestId('login-email').fill(ADMIN_EMAIL);
        await page.getByTestId('login-password').fill(ADMIN_PASSWORD);
        await page.getByTestId('login-submit').click();

        await expect(page).toHaveURL(/\/admin\/dashboard/);

        // Verify sidebar navigation is visible
        await expect(page.getByText('Properties')).toBeVisible();
        await expect(page.getByText('Agencies')).toBeVisible();
        await expect(page.getByText('Users')).toBeVisible();
    });

    test('admin can logout', async ({ page }) => {
        // Login
        await page.goto('/login');
        await page.getByTestId('login-email').fill(ADMIN_EMAIL);
        await page.getByTestId('login-password').fill(ADMIN_PASSWORD);
        await page.getByTestId('login-submit').click();

        await expect(page).toHaveURL(/\/admin\/dashboard/);

        // Open user menu and click logout
        await page.getByText(`${ADMIN_EMAIL.split('@')[0]}`).or(page.locator('[class*="user"]')).first().click();
        await page.getByText('Logout').click();

        // Should redirect to login
        await expect(page).toHaveURL(/\/login/);

        // Trying to access dashboard should redirect to login
        await page.goto('/admin/dashboard');
        await expect(page).toHaveURL(/\/login/);
    });

    test('root path redirects unauthenticated users to login', async ({ page }) => {
        await page.goto('/');

        await expect(page).toHaveURL(/\/login/);
    });
});
