<?php

namespace Goldfinch\IconField\Forms;

use Goldfinch\IconField\ORM\FieldType\DBIcon;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use Swordfox\Vite\Helpers\Vite;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class IconField extends FormField
{
    protected $schemaDataType = 'IconField';

    protected $iconsSet = null;

    protected $iconsSetConfig = null;

    protected $iconsList = [];

    /**
     * @var HiddenField
     */
    protected $fieldData = null;

    /**
     * @var FormField
     */
    protected $fieldKey = null;

    /**
     * Gets field for the key selector
     *
     * @return FormField
     */
    public function getKeyField()
    {
        return $this->fieldKey;
    }

    /**
     * Gets field for the data input
     *
     * @return HiddenField
     */
    public function getDataField()
    {
        return $this->fieldData;
    }

    public function getPreviewField()
    {
        return LiteralField::create(
            $this->getName().'Icon',
            '<div class="ggp__preview" data-goldfinch-icon="preview"></div>',
        );
    }

    public function getCurrentIcons()
    {
        $cfg = $this->iconsSetConfig;
        $value = $this->getKeyField()->dataValue();
        if ($value) {
            $values = explode(',', $value);
        }
        $iconsList = $this->iconsList;

        $html = '';

        $count = 0;

        if (isset($values)) {

            $count = count($values);

            foreach ($values as $v) {
                $icon = $this->getIconByKey($v);

                if (isset($icon['admin_template'])) {
                    $html .= '<li data-value="'.$v.'">'.$icon['admin_template'].'</li>';
                }
            }
        }

        $return = DBHTMLText::create();
        $return->setValue('<ul data-count="'.$count.'">'.$html.'</ul>');

        return $return;
    }

    public function __construct($set, $name, $title = null, $value = '', $static = false)
    {
        $this->setName($name);
        $this->fieldData = HiddenField::create("{$name}[Data]", 'Data');

        $this->fieldData->setAttribute('data-goldfinch-icon', 'data');

        $this->buildKeyField();

        $this->initSetsRequirements();

        if (! $static) {

            $this->setIconsSet($set);

            Requirements::css('goldfinch/icon-field:client/dist/icon-styles.css');
            Requirements::javascript('goldfinch/icon-field:client/dist/icon.js');

            $this->setIconsList();
        }

        if (! $this->iconsSetConfig) {
            $this->setDescription('<span style="color: red">The set <b>'.$set.'</b> does not exist in YAML config.</span>');
        }

        parent::__construct($name, $title, $value);
    }

    public function getIconsConfigJSON()
    {
        return json_encode($this->iconsSetConfig);
    }

    public function getIconsListJSON()
    {
        return json_encode($this->iconsList);
    }

    public function getIconsList()
    {
        return ArrayList::create($this->iconsList);
    }

    private function setIconsList(): void
    {
        $cfg = $this->iconsSetConfig;

        $cfgHash = md5($this->iconsSet.json_encode($cfg));

        $cache = Injector::inst()->get(CacheInterface::class.'.GoldfinchIconField');

        if ($cache->has($cfgHash)) {

            $this->iconsList = json_decode($cache->get($cfgHash), true);

            return;
        }

        /*
            $schemaList = [
                0 => [
                    'title' => '', // optional
                    'value' => 'value-icon-prior', // optional (used prior the key)
                    'source' => '', // for display purpose (can be a full link, filename with extension etc.)
                    'template' => '', // added at the backend (not for customizations)
                ],
            ];
        */
        $schemaList = [];

        if (! isset($cfg['type'])) {
            return;
        }

        if ($cfg['type'] == 'font') {

            $fs = new Filesystem;

            $schema = BASE_PATH.'/app/_schema/icon-'.$this->iconsSet.'.json';

            if ($fs->exists($schema)) {
                $content = file_get_contents($schema);
                $content = json_decode($content, true);

                if ($content && is_array($content) && count($content)) {

                    $schemaList = $content;

                    foreach ($schemaList as $k => $sl) {
                        if (! isset($sl['value']) || $sl['value'] == '') {
                            $sl['value'] = $k;
                        }

                        $sl['admin_template'] = $this->renderIconAdminTemplate($sl);

                        // commented out as seem to be unused
                        // if (!isset($sl['template']) || $sl['template'] == '') {
                        //     $sl['template'] = $this->renderIconTemplate($sl);
                        // }

                        $schemaList[$k] = $sl;
                    }
                }
            }

        } elseif ($cfg['type'] == 'dir') {

            $sourcePath = '/'.$cfg['source'];

            $finder = new Finder();
            $files = $finder->in(PUBLIC_PATH.$sourcePath)->files();

            foreach ($files as $file) {

                $filename = $file->getFilename();
                $ex = explode('.', $filename);

                $item = [
                    'title' => '',
                    'value' => $ex[0],
                    'source' => $sourcePath.'/'.$filename,
                ];
                $item['admin_template'] = $this->renderIconAdminTemplate($item);
                // $item['template'] = $this->renderIconTemplate($item); // commented out as seem to be unused
                $schemaList[] = $item;
            }

        } elseif ($cfg['type'] == 'upload') {

            $targetFolder = File::get()->filter(['ClassName' => Folder::class, 'Name' => $cfg['source']])->first();

            if ($targetFolder) {

                // $folder = File::get()->byID(1);

                // if ($folder && $folder == Folder::class) {
                if ($targetFolder && $targetFolder == Folder::class) {
                    // foreach ($folder->myChildren() as $file) {
                    foreach ($targetFolder->myChildren() as $file) {

                        $item = [
                            'title' => $file->Title,
                            'value' => $file->ID,
                            'source' => $file->getURL(),
                        ];

                        $item['admin_template'] = $this->renderIconAdminTemplate($item);
                        // $item['template'] = $this->renderIconTemplate($item); // commented out as seem to be unused
                        $schemaList[] = $item;
                    }
                }
            } else {
                // specified folder in .yml is not found
            }

        } elseif ($cfg['type'] == 'json') {

            $fs = new Filesystem;

            $schema = BASE_PATH.'/app/_schema/'.$cfg['source'];

            if ($fs->exists($schema)) {
                $content = file_get_contents($schema);
                $content = json_decode($content, true);

                if ($content && is_array($content) && count($content)) {

                    $schemaList = $content;

                    foreach ($schemaList as $k => $sl) {
                        if (! isset($sl['value']) || $sl['value'] == '') {
                            $sl['value'] = $k;
                        }

                        $sl['admin_template'] = $this->renderIconAdminTemplate($sl);

                        // commented out as seem to be unused
                        // if (!isset($sl['template']) || $sl['template'] == '') {
                        //     $sl['template'] = $this->renderIconTemplate($sl);
                        // }

                        $schemaList[$k] = $sl;
                    }
                }
            }

        }

        $this->iconsList = $schemaList;

        $cache->set($cfgHash, json_encode($schemaList), 3600);
    }

    private function renderIconAdminTemplate($item): string
    {
        return $this->renderIconTemplate($item, true);
    }

    public function renderIconTemplate($item, $admin = false, $set = null, $value = null): string
    {
        if (! $set) {
            $cfg = $this->iconsSetConfig;
        } else {
            $cfg = $set;
        }

        $render = '';

        if ($admin) {
            $primaryPath = 'Goldfinch/IconField/Types/Admin/';
        } else {
            $primaryPath = 'Goldfinch/IconField/Types/';
        }

        if ($cfg['type'] == 'font') {

            $template = $primaryPath.'FontItem';

        } elseif ($cfg['type'] == 'dir') {

            $template = $primaryPath.'DirItem';

        } elseif ($cfg['type'] == 'upload') {

            $template = $primaryPath.'UploadItem';

        } elseif ($cfg['type'] == 'json') {

            $template = $primaryPath.'JsonItem';

        }

        if ($value) {
            $item['value'] = $value;
        }

        if (! isset($item['title']) || ! $item['title']) {
            $item['title'] = $item['value'];
        }

        if (isset($item['source']) && $item['source'] && $item['source'] != '') {
            $ext = explode('.', $item['source']);
            $ext = end($ext);
        } else {
            $ext = null;
        }

        if ($admin) {

            if ($cfg['type'] == 'upload' || $cfg['type'] == 'dir' || $cfg['type'] == 'json') {

                $inlineStyle = [
                    'display' => 'inline-block',
                    'width' => '32px',
                    'height' => '32px',
                ];

                if (isset($cfg['vector']) && $cfg['vector'] === false) {
                    $inlineStyle += [
                        'background-size' => 'contain',
                        'background-repeat' => 'no-repeat',
                        'background-position' => 'center',
                        'background-image' => 'url('.$item['source'].')',
                    ];
                } else {
                    $inlineStyle += [
                        'mask-size' => 'contain',
                        'mask-repeat' => 'no-repeat',
                        'mask-position' => 'center',
                        'mask-image' => 'url('.$item['source'].')',
                        'background-color' => '#43536d',
                    ];
                }
            }

        } else {

            $inlineStyle = [];

            // defaults
            if ($cfg['type'] == 'upload' || $cfg['type'] == 'dir' || $cfg['type'] == 'json') {

                $inlineStyle = [
                    'display' => 'inline-block',
                    'width' => '32px',
                    'height' => '32px',
                ];

                if (isset($cfg['vector']) && $cfg['vector'] === false) {
                    $inlineStyle += [
                        'background-size' => 'contain',
                        'background-repeat' => 'no-repeat',
                        'background-position' => 'center',
                        'background-image' => 'url('.$item['source'].')',
                    ];
                } else {
                    $inlineStyle += [
                        'mask-size' => 'contain',
                        'mask-repeat' => 'no-repeat',
                        'mask-position' => 'center',
                        'mask-image' => 'url('.$item['source'].')',
                        'background-color' => '#43536d',
                    ];
                }
            }

            // apply custom styles

            if (isset($item['color'])) {
                if ($cfg['type'] == 'font') {
                    $inlineStyle['color'] = $item['color'];
                } elseif ($ext == 'svg') {
                    $inlineStyle['background-color'] = $item['color'];
                }
            }

            if (isset($item['size'])) {
                $size = (int) $item['size'];

                if ($size) {
                    if ($cfg['type'] == 'font') {
                        $inlineStyle['font-size'] = $item['size'].'px';
                    } else {
                        $inlineStyle['width'] = $item['size'].'px';
                        $inlineStyle['height'] = $item['size'].'px';
                    }
                }
            }
        }

        $inlineStyleStr = '';

        if (! empty($inlineStyle)) {
            foreach ($inlineStyle as $prop => $style) {
                $inlineStyleStr .= $prop.':'.$style.';';
            }
        }

        // ! takes too much time to load with thousands of icons and multiple icon fields on the page
        // return $this->customise(ArrayData::create(['Icon' => $item, 'InlineStyle' => $inlineStyleStr]))->renderWith($template)->RAW();

        // !do template render in place instead (only for admin template for now, as the front-end templates can be re-declared by the user)
        if (strpos($template, 'Types/Admin') !== false) {

            // if (
            //     $template == 'Goldfinch/IconField/Types/Admin/DirItem' ||
            //     $template == 'Goldfinch/IconField/Types/Admin/JsonItem' ||
            //     $template == 'Goldfinch/IconField/Types/Admin/UploadItem'
            // ) {
            //     return '<i title="'.$item['title'].'" class="'.$item['value'].'"></i>';
            // } else if ($template == 'Goldfinch/IconField/Types/Admin/FontItem') {
            //     return '<i title="'.$item['title'].'" class="'.$item['value'].'"'.($inlineStyleStr ? ' style="'.$inlineStyleStr.'"' : '').'></i>';
            // }
            return '<i title="'.$item['title'].'" class="'.$item['value'].'"'.($inlineStyleStr ? ' style="'.$inlineStyleStr.'"' : '').'></i>';
        } else {
            // ! probably not in used (since ['template'] is commented out)

            // front-end (through ss template)
            return $this->customise(ArrayData::create(['Icon' => $item, 'InlineStyle' => $inlineStyleStr]))->renderWith($template)->RAW();
        }
    }

    private function setIconsSet($set): void
    {
        $this->iconsSet = $set;

        if ($sets = $this->config()->get('icons_sets')) {
            foreach ($sets as $type => $s) {
                if (isset($s['type']) && $set == $type) {
                    $this->iconsSetConfig = $s;
                    break;
                }
            }
        }
    }

    private function initSetsRequirements(): void
    {
        $sets = $this->config()->get('icons_sets');

        $fonts = [];

        if ($sets) {
            foreach ($sets as $set) {
                if ($set['type'] == 'font' && isset($set['source'])) {
                    $fonts[] = $set['source'];
                }
            }
        }

        if ($fonts && is_array($fonts)) {
            foreach ($fonts as $include) {

                // vite link
                if (substr($include, 0, 5) == 'vite:') {
                    $include = substr($include, 5);
                    $include = Vite::assetLink($include);
                }

                Requirements::css($include);
            }
        }
    }

    public function __clone()
    {
        $this->fieldData = clone $this->fieldData;
        $this->fieldKey = clone $this->fieldKey;
    }

    /**
     * Builds a new icon key field
     *
     * @return FormField
     */
    protected function buildKeyField()
    {
        $name = $this->getName();

        $keyValue = $this->fieldKey
            ? $this->fieldKey->dataValue()
            : null;

        $field = HiddenField::create("{$name}[Key]", 'Key');

        $field->setReadonly($this->isReadonly());
        $field->setDisabled($this->isDisabled());
        if ($keyValue) {
            $field->setValue($keyValue);
        }

        $field->setAttribute('data-goldfinch-icon', 'key');

        $this->fieldKey = $field;

        return $field;
    }

    public function getIconByKey($key)
    {
        $list = $this->iconsList;
        $item = null;

        foreach ($list as $icon) {
            if ($icon['value'] == $key) {
                $item = $icon;
                break;
            }
        }

        return $item;
    }

    public function setSubmittedValue($value, $data = null)
    {
        if (empty($value)) {
            $this->value = null;
            $this->fieldKey->setValue(null);
            $this->fieldData->setValue(null);

            return $this;
        }

        if (is_string($value)) {
            $value = $this->dataBundle($value);
        } else {
            $value = $this->dataBundle($value['Key']);
            $value['Data'] = json_encode($value['Data']);
        }

        // Update each field
        $this->fieldKey->setSubmittedValue($value['Key'], $value);
        $this->fieldData->setSubmittedValue($value['Data'], $value);

        // Get data value
        $this->value = $this->dataValue();

        return $this;
    }

    public function setValue($value, $data = null)
    {
        if (empty($value)) {
            $this->value = null;
            $this->fieldKey->setValue(null);
            $this->fieldData->setValue(null);

            return $this;
        }

        if ($value instanceof DBIcon) {
            $stock = [
                'Key' => $value->getKey(),
                'Data' => $value->getData(),
            ];
        } else {
            throw new InvalidArgumentException('Invalid icon format');
        }

        // dump(2, $this->dataBundle($value->getKey()));
        // if (!isset($stock['Data']) || !$stock['Data']) {
        $stock = $this->dataBundle($value->getKey());
        // }

        // Save value
        $this->fieldKey->setValue($stock['Key']);
        $this->fieldData->setValue($stock['Data']);
        $this->value = $this->dataValue();

        return $this;
    }

    private function dataBundle($key)
    {
        $set = $this->iconsSetConfig;
        $item = $this->getIconByKey($key);

        return [
            'Key' => $key,
            'Data' => [
                'set' => [
                    'name' => $this->iconsSet,
                    'type' => isset($set['type']) ? $set['type'] : null,
                    'vector' => isset($set['vector']) ? $set['vector'] : true,
                    // 'source' => $set['source'],
                ],
                'title' => $item && isset($item['title']) ? $item['title'] : '',
                // 'value' => $item && isset($item['value']) ? $item['value'] : null,
                'source' => $item && isset($item['source']) ? $item['source'] : '',
            ],
        ];
    }

    /**
     * Get value as DBIcon object useful for formatting the number
     *
     * @return DBIcon
     */
    protected function getDBIcon()
    {
        return DBIcon::create_field('Icon', [
            'Key' => $this->fieldKey->dataValue(),
            'Data' => $this->fieldData->dataValue(),
        ]);
    }

    public function dataValue()
    {
        // Non-localised
        return $this->getDBIcon()->getValue();
    }

    public function Value()
    {
        // Localised
        return $this->getDBIcon()->getValue()->Nice();
    }

    /**
     * @param  DataObjectInterface|object  $dataObject
     */
    public function saveInto(DataObjectInterface $dataObject)
    {
        $fieldName = $this->getName();
        if ($dataObject->hasMethod("set$fieldName")) {
            $dataObject->$fieldName = $this->getDBIcon();
        } else {
            $keyField = "{$fieldName}Key";
            $dataField = "{$fieldName}Data";

            $dataObject->$keyField = $this->fieldKey->dataValue();

            if (
                $dataObject->$keyField &&
                $dataObject->$keyField != ''
            ) {
                $dataObject->$dataField = $this->fieldData->dataValue();
            } else {
                $dataObject->$dataField = null;
            }
        }
    }

    /**
     * Returns a readonly version of this field.
     */
    public function performReadonlyTransformation()
    {
        $clone = clone $this;
        $clone->setReadonly(true);

        return $clone;
    }

    public function setReadonly($bool)
    {
        parent::setReadonly($bool);

        $this->fieldData->setReadonly($bool);
        $this->fieldKey->setReadonly($bool);

        return $this;
    }

    public function setDisabled($bool)
    {
        parent::setDisabled($bool);

        $this->fieldData->setDisabled($bool);
        $this->fieldKey->setDisabled($bool);

        return $this;
    }

    public function iconHidePreview()
    {
        $this->addExtraClass('goldfinch-icon-hide-preview');

        return $this;
    }

    /**
     * Validate this field
     *
     * @param  Validator  $validator
     * @return bool
     */
    public function validate($validator)
    {
        // return $this->extendValidationResult($result, $validator);
    }

    public function setForm($form)
    {
        $this->fieldKey->setForm($form);
        $this->fieldData->setForm($form);

        return parent::setForm($form);
    }
}
