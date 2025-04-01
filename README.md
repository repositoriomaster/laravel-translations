### Credits Original developer:

https://github.com/MohmmedAshraf/laravel-translations

### Introduction

Laravel Translations UI package provides a user-friendly interface for managing translations in your Laravel application. It simplifies tasks such as adding, editing, deleting, and exporting translations. The package also includes a handy search feature and the ability to invite collaborators for easy translation management. Currently, the package is integrated with the Google Translate API, allowing you to translate your content into any language of your choice.

> 📺 **[Watch a 4-minute video by Povilas Korop](https://www.youtube.com/watch?v=lYkgXnwnVbw)** showcasing the package.

---

#### Install from Scratch

After uninstallation, perform a fresh installation of the package.

```bash
composer require repositoriomaster/laravel-translations --with-all-dependencies
```

Before you can access the translations UI, you'll need to publish the package's assets and migrations files by running the following command:

```bash
php artisan translations:install
```

### Usage

To import your translations, run the following command:

```bash
php artisan translations:import
```

To import and overwrite all previous translations, use the following command:

```bash
php artisan translations:import --fresh
```

To access the translations UI, visit `/translations` in your browser. If you are using a production environment, you will need to create owner user first. To do so, run the following command:

```bash
php artisan translations:contributor
``` 

This command will prompt you to enter the user's name, email, and password. Once you have created the owner user, you can log in to the translations UI dashboard and start managing your translations.

#### Exporting Translations

You can export your translations from the translations UI dashboard or by running the following command:

```bash
php artisan translations:export
```

### Configuration
You can configure the package and set your base language by publishing the configuration file:

```bash
php artisan vendor:publish --tag=translations-config
```

This will publish the `translations.php` configuration file to your `config` directory.

### Upgrading

When upgrading to a new major version of Laravel Translations UI, it's important that you carefully review the upgrade guide.

In addition, when upgrading to any new Translations UI version, you should re-publish Translations UI assets:

```bash
php artisan translations:publish
```

To keep the assets up-to-date and avoid issues in future updates, you may add the translations:publish command to the post-update-cmd scripts in your application's composer.json file:

```json
{
    "scripts": {
        "post-update-cmd": [
            "@php artisan translations:publish --ansi"
        ]
    }
}
```
