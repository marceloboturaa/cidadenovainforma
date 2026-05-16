# CSS organization

The old `public.css` and `admin.css` files are compatibility manifests only.
Edit the module files instead:

- `public/`: public site, news, events, articles, institution pages and responsive rules.
- `admin/`: authentication, panel shell, editor, users, education, documents, forum and responsive rules.

The load order is defined in `config/assets.php`. When adding a new CSS module,
add it there in the exact order it should be applied and mirror it in the
manifest if direct loading of `public.css` or `admin.css` still matters.
