# Contributing to Laravel DB Provisioner

Thank you for your interest in contributing to Laravel DB Provisioner! This guide will help you set up your local development environment and test your changes.

## Table of Contents

- [Local Development Setup](#local-development-setup)
- [Testing the Package](#testing-the-package)
- [Development Workflow](#development-workflow)
- [Running Tests](#running-tests)

## Local Development Setup

### Clone the Repository

Clone the package into a directory where you keep your projects:

```bash
cd /path/to/your/projects
git clone https://github.com/SubhanRaj/laravel-db-provisioner.git
cd laravel-db-provisioner
```

### Create or Use a Test Laravel Project

Create a new Laravel project for testing:

```bash
cd /path/to/your/projects
composer create-project laravel/laravel test-db-setup
cd test-db-setup
```

Or use an existing Laravel project.

### Configure Composer Local Repository

In your test Laravel project's `composer.json`, add a local repository pointing to your cloned package:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../laravel-db-provisioner",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "laravel/framework": "^11.0",
        "subhanraj/laravel-db-provisioner": "@dev"
    }
}
```

Then install dependencies:

```bash
composer install
```

Verify the symlink was created:

```bash
ls -la vendor/subhanraj/
```

You should see `laravel-db-provisioner` listed with an arrow (`->`) indicating it's symlinked.

## Testing the Package

### Verify Package Discovery

Check that the service provider was auto-discovered:

```bash
php artisan package:discover
```

You should see `Subhanraj\LaravelDbProvisioner\LaravelDbProvisionerServiceProvider` listed.

### Run the Command

Test the main command:

```bash
php artisan db:provision
```

You'll be prompted for:
- Database admin username (defaults to `root`)
- Database admin password (leave blank if your setup has no password)

### Verify Results

Check your `.env` file was updated:

```bash
grep -i "DB_" .env
```

You should see entries like:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=test_db_setup_local
DB_USERNAME=test_db_setup_local
DB_PASSWORD=<random_16_chars>
```

Verify the database was created:

```bash
mysql -u root -p -h 127.0.0.1 -e "SHOW DATABASES LIKE '%_local';"
```

Verify the user was created:

```bash
mysql -u root -p -h 127.0.0.1 -e "SELECT User, Host FROM mysql.user WHERE User LIKE '%test_db_setup%';"
```

### Test Laravel Integration

Confirm the database connection works by running migrations:

```bash
php artisan migrate
```

The migrations should execute successfully with your newly created database and user.

## Development Workflow

### Making Changes

As you edit the package code:

1. Edit files in `/path/to/your/projects/laravel-db-provisioner/src/`
2. Changes are immediately reflected in your test project (since it's symlinked)
3. No need to run `composer install` after code edits (unless you change `composer.json`)
4. Test your changes: `php artisan db:provision`

### Testing Complete Workflow

To test from scratch:

1. **Delete the test database and user**:
   ```bash
   mysql -u root -p -h 127.0.0.1 -e "DROP DATABASE test_db_setup_local; DROP USER 'test_db_setup_local'@'127.0.0.1'; DROP USER 'test_db_setup_local'@'localhost';"
   ```

2. **Reset the `.env` file**:
   ```bash
   rm .env
   ```

3. **Run the command again**:
   ```bash
   php artisan db:provision
   ```

4. **Verify**: `php artisan migrate`

### Common Changes to Test

**Changing the database naming logic:**
- Edit `src/Commands/ProvisionDatabaseCommand.php` → `slugify()` method
- Test with different app names in `.env`

**Adding validation or error handling:**
- Edit any method and re-run `php artisan db:provision`

**Updating the success message:**
- Edit `outputSuccess()` method
- Run command to see new output

## Running Tests

### Unit Tests (if available)

```bash
vendor/bin/phpunit
```

### Code Quality

Check for code style issues:

```bash
vendor/bin/phpcs
```

### Manual Testing Checklist

Before submitting a pull request, ensure:

- [ ] Code runs without errors
- [ ] Database is created successfully
- [ ] Database users are created with correct privileges
- [ ] `.env` file is updated correctly
- [ ] Laravel migrations execute successfully
- [ ] Works with blank passwords (XAMPP/Laragon style)
- [ ] Works with password-protected databases (Homebrew style)
- [ ] Works with custom DB_HOST and DB_PORT values

## Troubleshooting

### Command Not Found

- Run `php artisan list` to verify the command is available
- Run `php artisan cache:clear` and `php artisan config:clear`
- Verify the symlink exists: `ls -l vendor/subhanraj/laravel-db-provisioner`

### Permission Errors

- Ensure your database server is running
- Verify admin credentials:
  ```bash
  mysql -u root -p -h 127.0.0.1
  ```

### Symlink Not Created

- Manually verify: `ls -l vendor/subhanraj/laravel-db-provisioner`
- If not symlinked, reinstall:
  ```bash
  composer remove subhanraj/laravel-db-provisioner
  composer install
  ```

### Cannot Update .env

- Ensure file has write permissions: `chmod 644 .env`
- Check the file is not corrupted

## Submitting Changes

1. Create a feature branch: `git checkout -b feature/your-feature-name`
2. Make your changes and test thoroughly
3. Commit with clear messages: `git commit -m "feat: describe your change"`
4. Push to your fork: `git push origin feature/your-feature-name`
5. Open a pull request with a description of your changes

## Code Style

- Follow PSR-12 coding standards
- Use meaningful variable and function names
- Add comments for complex logic
- Keep methods focused and single-purpose

## Questions?

Feel free to open an issue on GitHub if you have questions about contributing.

Thank you for your contributions! 🎉
