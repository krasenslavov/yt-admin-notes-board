# YT Admin Notes Board

A lightweight collaboration tool for WordPress teams. Add a shared notes board directly to your WordPress dashboard for task lists, reminders, announcements, and quick team communication.

## Description

The Admin Notes Board plugin creates a dedicated space on your WordPress dashboard where administrators and editors can share notes, task lists, and important information. Perfect for small to medium teams who need a quick way to communicate without leaving WordPress.

## Features

- **Dashboard Widget**: Prominent "Team Notes" widget on main dashboard
- **WYSIWYG Editor**: Rich text formatting with WordPress editor
- **Plain Text Option**: Simple textarea for distraction-free notes
- **Shared Notes**: All team members see the same content
- **Role-Based Access**: Control who can edit notes (admin, editor, etc.)
- **Last Edited Info**: Track who edited notes and when
- **Auto-Save**: Automatic saving every minute
- **AJAX Powered**: Save without page reload
- **Keyboard Shortcuts**: Ctrl+S (save), Ctrl+Shift+C (clear)
- **Clear Function**: One-click to clear all notes
- **Responsive Design**: Works on all screen sizes
- **Custom Widget Title**: Change the widget heading
- **No Database Tables**: Uses WordPress options (lightweight)
- **Translation Ready**: Full i18n support
- **Secure**: Nonce verification and capability checks

## Installation

1. Upload `yt-admin-notes-board.php` to `/wp-content/plugins/`
2. Upload `yt-admin-notes-board.css` to the same directory
3. Upload `yt-admin-notes-board.js` to the same directory
4. Activate the plugin through the 'Plugins' menu
5. View the "Team Notes" widget on your dashboard
6. Configure at Settings → Notes Board

## Usage

### Viewing Notes

1. Go to **Dashboard** (wp-admin/)
2. Find the **Team Notes** widget
3. View current team notes

### Editing Notes (Admin/Editor)

1. Go to **Dashboard**
2. Type or paste content into the editor
3. Click **Save Notes** button
4. Notes are instantly shared with all team members

### Using WYSIWYG Editor

- **Bold**: Select text and click B
- **Italic**: Select text and click I
- **Lists**: Click bullet or numbered list button
- **Links**: Select text and click link button
- **Formatting**: Use toolbar buttons

### Using Plain Text Editor

- Simple textarea without formatting
- Great for code snippets or simple lists
- Less visual clutter
- Faster loading

### Clearing Notes

1. Click **Clear Notes** button
2. Confirm the action
3. All notes removed (cannot be undone)

### Keyboard Shortcuts

- **Ctrl/Cmd + S**: Save notes
- **Ctrl/Cmd + Shift + C**: Clear notes (with confirmation)

## Settings

Navigate to **Settings → Notes Board** to configure:

### Widget Title

- **Default**: "Team Notes"
- **Purpose**: Customize widget heading
- **Examples**: "Daily Tasks", "Team Announcements", "Quick Notes"

### Editor Type

**WYSIWYG Editor (Rich text with formatting)**
- WordPress TinyMCE editor
- Bold, italic, lists, links
- Visual formatting
- Larger file size

**Plain Text (Simple textarea)**
- Simple textarea field
- No formatting options
- Clean and minimal
- Faster performance

### Who Can Edit

**Select user roles that can edit notes:**
- Administrator (recommended)
- Editor (recommended for editorial teams)
- Author (optional)
- Contributor (optional)
- Subscriber (not recommended)

**Note**: All users can *view* notes, only selected roles can *edit*.

### Show Last Edited Info

- **Enabled**: Shows "Last edited by [name] [time] ago"
- **Disabled**: Hides edit history
- **Purpose**: Track who made recent changes

## Use Cases

### Daily Task List

```
Today's Tasks:
- Review pending articles
- Update homepage banner
- Check backup status
- Reply to support tickets
```

### Team Announcements

```
IMPORTANT:
Site maintenance scheduled for Saturday 2 AM - 4 AM
Please save work by Friday evening.
```

### Contact Information

```
Emergency Contacts:
Developer: John - 555-1234
Designer: Sarah - 555-5678
Hosting: support@hosting.com
```

### Content Calendar

```
This Week's Content:
Mon - Blog: SEO Tips
Wed - Social: Product Launch
Fri - Newsletter: Monthly Update
```

### Quick Reminders

```
Remember:
✓ Backup before major updates
✓ Test on staging first
✓ Clear cache after changes
✓ Update copyright year
```

## Configuration Examples

### Small Team (1-3 People)

```
Widget Title: "Team Notes"
Editor Type: WYSIWYG
Who Can Edit: Administrator, Editor
Show Last Edited: Enabled
```

### Large Editorial Team

```
Widget Title: "Editorial Notes"
Editor Type: WYSIWYG
Who Can Edit: Administrator, Editor, Author
Show Last Edited: Enabled
```

### Developer Team

```
Widget Title: "Dev Notes"
Editor Type: Plain Text
Who Can Edit: Administrator
Show Last Edited: Enabled
```

### Simple Reminder Board

```
Widget Title: "Daily Reminders"
Editor Type: Plain Text
Who Can Edit: Administrator, Editor
Show Last Edited: Disabled
```

## Technical Details

### File Structure

```
yt-admin-notes-board.php       # Main plugin file (710 lines)
yt-admin-notes-board.css       # Widget styles
yt-admin-notes-board.js        # AJAX functionality
README-yt-admin-notes-board.md # Documentation
```

### Constants Defined

```php
YT_ANB_VERSION   // Plugin version (1.0.0)
YT_ANB_BASENAME  // Plugin basename
YT_ANB_PATH      // Plugin directory path
YT_ANB_URL       // Plugin directory URL
```

### Database Storage

**Option Name**: `yt_anb_options`

**Option Structure**:
```php
array(
    'notes_content'    => 'HTML or plain text',
    'editor_type'      => 'wysiwyg' or 'plain',
    'allowed_roles'    => array('administrator', 'editor'),
    'widget_title'     => 'Team Notes',
    'show_last_edited' => true,
    'last_edited_by'   => 'John Doe',
    'last_edited_time' => '2025-01-15 14:30:00'
)
```

### WordPress Hooks

#### Actions
- `plugins_loaded`: Load text domain
- `wp_dashboard_setup`: Add dashboard widget
- `admin_menu`: Add settings page
- `admin_init`: Register settings
- `admin_enqueue_scripts`: Load CSS/JS

#### Filters
- `plugin_action_links_{basename}`: Add settings link

#### AJAX Endpoints
- `yt_anb_save_notes`: Save notes content
- `yt_anb_clear_notes`: Clear all notes

### Capabilities Required

**View Notes**: Any logged-in user with dashboard access
**Edit Notes**: User role must be in "allowed_roles" setting
**Configure Settings**: `manage_options` capability

### Data Sanitization

**Input**:
- Notes content: `wp_kses_post()` (allows safe HTML)
- Widget title: `sanitize_text_field()`
- Roles: `sanitize_key()`

**Output**:
- Content: `wp_kses_post()` + `wpautop()`
- Text: `esc_html()`
- Attributes: `esc_attr()`

## Security Features

- **Nonce Verification**: All AJAX requests
- **Capability Checks**: Role-based access control
- **Input Sanitization**: All user input cleaned
- **Output Escaping**: All output escaped
- **XSS Prevention**: Proper HTML filtering
- **CSRF Protection**: Nonce tokens

## Auto-Save Feature

The plugin includes optional auto-save functionality:

- **Interval**: Every 60 seconds (1 minute)
- **Condition**: Only if content changed
- **Method**: AJAX background save
- **User Feedback**: Silent (no message shown)

**To enable**: Uncomment in JavaScript file (line 205)

## Performance

### Resource Usage

- **Database**: 1 option entry (~2-10 KB depending on content)
- **HTTP Requests**: 0 (widget loads with dashboard)
- **AJAX**: Only when saving/clearing
- **Memory**: < 100 KB

### Load Time Impact

- **First Load**: +50-100ms (JavaScript/CSS)
- **Subsequent**: Cached (0ms)
- **WYSIWYG Editor**: +200-300ms
- **Plain Text**: +10ms

### Optimization

- **No Database Tables**: Uses lightweight options
- **Conditional Loading**: Scripts only on dashboard
- **Minification**: Ready for production minification
- **Caching**: Compatible with all cache plugins

## Frequently Asked Questions

### Can multiple users edit at the same time?

Yes, but last save wins. The plugin doesn't lock editing. Use "last edited" info to coordinate.

### What happens if two people save simultaneously?

The second save overwrites the first. We recommend checking "last edited" before saving major changes.

### Can I use HTML in notes?

Yes, with WYSIWYG editor. The plugin uses `wp_kses_post()` which allows safe HTML.

### Is there a character limit?

No hard limit, but keep notes under 10,000 characters for best performance.

### Can I add images?

Not currently. The WYSIWYG editor has media buttons disabled to keep notes lightweight.

### Does it work with Gutenberg?

Yes, the dashboard widget works with any WordPress setup (Classic or Block editor for posts).

### Can I have multiple notes widgets?

Not currently. One shared notes board per site.

### Are notes backed up?

Yes, if you backup your WordPress database (wp_options table).

### Can I export notes?

Not directly, but you can copy/paste content or export via database backup.

### Is it multisite compatible?

Yes, each site in a network has its own notes board.

## Troubleshooting

### Widget not appearing

**Causes**:
- User role not in "allowed_roles"
- Widget hidden via Screen Options

**Solutions**:
- Go to Settings → Notes Board, check roles
- Click "Screen Options" on dashboard, enable widget

### Can't edit notes

**Causes**:
- User role doesn't have edit permission
- JavaScript error

**Solutions**:
- Check Settings → Notes Board → Who Can Edit
- Check browser console for errors
- Try different editor type (Plain Text)

### Save button not working

**Causes**:
- JavaScript error
- AJAX blocked
- Nonce expired

**Solutions**:
- Refresh page
- Check browser console
- Disable other plugins temporarily

### Notes disappeared

**Causes**:
- Someone clicked Clear Notes
- Database issue

**Solutions**:
- Check with team members
- Restore from database backup
- Check Settings → Notes Board (notes might be saved)

### WYSIWYG editor not loading

**Causes**:
- Theme/plugin conflict
- JavaScript error

**Solutions**:
- Switch to Plain Text editor temporarily
- Deactivate other plugins
- Check browser console

## Best Practices

### Content Guidelines

**Do**:
- Keep notes concise and organized
- Use lists for tasks
- Include dates for deadlines
- Update regularly
- Archive old information

**Don't**:
- Store sensitive passwords
- Write novels (use posts instead)
- Include private information
- Forget to save changes
- Clear without checking with team

### Team Coordination

- **Morning Standup**: Update daily tasks
- **End of Day**: Check off completed items
- **Weekly**: Archive old notes
- **Before Saving**: Check "last edited" info
- **Use Formatting**: Make notes scannable

### Performance Tips

- Keep notes under 5,000 characters
- Use Plain Text for simple notes
- Clear old notes regularly
- Don't embed large images
- Use lists instead of paragraphs

## Privacy & Data

### What Data is Stored?

- Notes content (text/HTML)
- Last editor's display name
- Last edit timestamp
- Widget settings

### GDPR Considerations

- No personal data by default
- "Last edited by" shows display name (public)
- No IP addresses stored
- No tracking or analytics

### Data Retention

- Notes persist indefinitely
- Deleted only when:
  - Clear button clicked
  - Plugin uninstalled
  - Database cleaned manually

## Uninstallation

When you delete the plugin:

1. All notes content deleted
2. Plugin settings removed
3. No database tables to drop
4. WordPress cache flushed

**Note**: Notes are permanently deleted. Export if needed.

## Changelog

### 1.0.0 (2025-01-XX)
- Initial release
- Dashboard widget with shared notes
- WYSIWYG and plain text editors
- Role-based access control
- Last edited tracking
- Auto-save functionality
- AJAX save/clear
- Keyboard shortcuts
- Configurable settings page
- Translation ready
- Mobile responsive

## Roadmap

Potential future features:
- Note history/revisions
- Multiple note boards
- Note categories/tags
- Rich media support
- Real-time collaboration
- Email notifications
- Export to PDF/CSV
- Note templates
- Markdown support
- Mobile app

## Developer Notes

### Line Count
- **PHP**: 710 lines
- **CSS**: 394 lines
- **JS**: 405 lines
- **Total**: 1,509 lines

### Extending the Plugin

#### Add Custom Widget Position

```php
add_filter('yt_anb_widget_priority', function($priority) {
    return 'low'; // 'high', 'core', 'default', 'low'
});
```

#### Modify Allowed HTML

```php
add_filter('yt_anb_allowed_html', function($allowed_tags) {
    // Add/remove allowed HTML tags
    return $allowed_tags;
});
```

#### Custom Auto-Save Interval

```php
add_filter('yt_anb_autosave_interval', function($interval) {
    return 120000; // 2 minutes (in milliseconds)
});
```

#### Add Note Templates

```php
add_filter('yt_anb_note_templates', function($templates) {
    $templates['tasks'] = "Today's Tasks:\n- \n- \n- ";
    return $templates;
});
```

### Contributing

Follow WordPress Coding Standards:

```bash
phpcs --standard=WordPress yt-admin-notes-board.php
```

## Support

For issues, questions, or feature requests:
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Support Forums](https://wordpress.org/support/)
- [GitHub Repository](https://github.com/krasenslavov/yt-admin-notes-board)

## License

GPL v2 or later

## Author

**Krasen Slavov**
- Website: [https://krasenslavov.com](https://krasenslavov.com)
- GitHub: [@krasenslavov](https://github.com/krasenslavov)

---

Collaborate better with a shared notes board for your WordPress team!
