# QueryCraft

QueryCraft is a flexible, shortcode-based WordPress plugin for building dynamic post queries with multiple pagination options. It’s designed to help developers and content managers quickly display custom post listings with advanced query capabilities, templating, and call-to-action (CTA) integration—all while offering an intuitive shortcode generator interface and extensibility through hooks and filters.

## Table of Contents

1. [Introduction](#introduction)
2. [Features](#features)
3. [Installation](#installation)
4. [Usage](#usage)
    - [Shortcode Basics](#shortcode-basics)
    - [Pagination Modes](#pagination-modes)
    - [Template System](#template-system)
    - [CTA Integration](#cta-integration)
5. [Shortcode Generator](#shortcode-generator)
6. [Developer Customization & Extensibility](#developer-customization--extensibility)
    - [Overriding Templates and CTAs](#overriding-templates-and-ctas)
    - [Hooks and Filters](#hooks-and-filters)
7. [Backup and Restore](#backup-and-restore)
8. [Frequently Asked Questions (FAQs)](#frequently-asked-questions-faqs)
9. [Changelog](#changelog)
10. [License](#license)

## Introduction

QueryCraft is built for flexibility and ease of use. It allows you to output custom queries using a simple `[load]` shortcode. With robust pagination options (numbered, load more, infinite scroll, and prev/next), a templating system that supports overrides in your theme, and a built-in call-to-action (CTA) mechanism, QueryCraft is designed to serve both developers and non-developers.

## Features

-   **Shortcode-Based Query Display:**  
    Easily embed dynamic queries into posts, pages, or widgets with the `[load]` shortcode.
-   **Advanced Query Building:**  
    Customize queries with options for post types, taxonomy filters, meta queries, ordering, and more.
-   **Multiple Pagination Options:**  
    Choose from numbered pagination, load more buttons, infinite scroll, or simple prev/next links.
-   **Template System:**  
    Render posts using customizable templates. Override default templates by placing files in your theme under `querycraft/templates`.
-   **CTA Integration:**  
    Insert call-to-action blocks into your listings at configurable intervals. Override or add new CTAs in your theme under `querycraft/cta`.
-   **Shortcode Generator Admin Page:**  
    A robust, intuitive admin interface to generate shortcodes without writing code.
-   **Backup & Restore on Activation/Deactivation:**  
    Automatically backs up the `querycraft` folder from your active theme on plugin deactivation and restores it on activation.
-   **Extensibility Through Hooks & Filters:**  
    Provides before/after loop hooks (and other extensibility points) so developers can customize output without modifying core code.
-   **Tailwind CSS Integration:**  
    Default templates are styled with Tailwind CSS, including plugins such as `@tailwindcss/line-clamp`, `tailwindcss-animate`, and `tailwindcss-elevation`.

## Installation

1. **Download/Clone QueryCraft:**
    - Download the plugin files from GitHub or install via your preferred method.
2. **Upload to WordPress:**
    - Upload the entire `querycraft` folder to the `/wp-content/plugins/` directory of your WordPress installation.
3. **Activate the Plugin:**
    - Log in to your WordPress admin area.
    - Navigate to **Plugins > Installed Plugins**.
    - Activate **QueryCraft**.
4. **On Activation:**
    - QueryCraft will create a `querycraft` folder in your active theme with `templates` and `cta` subdirectories.
    - A sample CTA file (`sample-cta.php`) will be generated in the `cta` folder.
    - If a backup of a previous customization exists, it will be restored automatically.

## Usage

### Shortcode Basics

Use the `[load]` shortcode to display custom queries. The shortcode accepts various attributes to customize the output. For example:

```plaintext
[load pt="post" display="6" paged="infinite_scroll" template="cards" cta_template="default" cta_interval="6"]
```
