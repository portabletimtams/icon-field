icons.yml

```yml
---
Name: app-icons
---
Goldfinch\IconField\Forms\IconFileField:
  icon_folder: 'assets/icons'

Goldfinch\IconField\Forms\IconFontField:
  icon_fonts:
    - 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css'
  icon_list:
    - bi-box-seam-fill
    - bi-bricks
    - bi-bug-fill
    - bi-earbuds
    - bi-duffle-fill
    # bi-box-seam-fill: bi-box-seam-fill
    # bi-bricks: bi-bricks
    # bi-bug-fill: bi-bug-fill
    # bi-earbuds: bi-earbuds
    # bi-duffle-fill: bi-duffle-fill
```

```yml
Goldfinch\IconField\Forms\IconField:
  icons_sets:
    # set_a:
    # type: font # font | dir | upload | json
    # source: "https://*" # link | dir | assets_dir | path
    # schema: "*.json"
    # multiple: false # true | false | numeric
    # search: true
    # search_show: 10
    # include: "*"
    # exclude: "*"
    # icon size # (for admin view)
    # icon color # (for admin view)
    set_a:
      type: font
      source: 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css'
    set_b:
      type: dir
      dir_save_rule: name # name | filename | full path
      source: 'ic'
    set_c:
      type: upload
      # allowed_extension:
      #   - svg # png etc.
      source: 'icons'
    set_d:
      type: json
      # allowed_extension:
      #   - svg # png etc.
      source: 'icon-external.json'
```

/\*\*

- $Icon.Color
  json: + (if svg)
  dir: + (if svg)
  upload: + (if svg)
  font: +

- $Icon.Size
  json: +
  dir: +
  upload: +
  font: +

- $Icon.WithAttr
- $Icon.WithClass

- loop $Icon (multiple)
  \*/

<%-- $Icon.IconSetName --%>
<%-- $Icon.IconType --%>

<!-- $Icon.Size(100).Color(green) -->

<h5>Font</h5>
<div>$IconFont</div>
<div>URL: $IconFont.URL</div>
<div>Title: $IconFont.Title</div>

<hr>

<h5>Dir</h5>
<div>$IconDir</div>
<div>URL: $IconDir.URL</div>
<div>Title: $IconDir.Title</div>

<hr>

<h5>Upload</h5>
<div>$IconUpload</div>
<div>URL: $IconUpload.URL</div>
<div>Title: $IconUpload.Title</div>

<hr>

<h5>Json</h5>
<div>$IconJSON</div>
<div>URL: $IconJSON.URL</div>
<div>Title: $IconJSON.Title</div>
