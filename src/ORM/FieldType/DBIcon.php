<?php

namespace Goldfinch\IconField\ORM\FieldType;

use Goldfinch\IconField\Forms\IconField;
use PhpTek\JSONText\ORM\FieldType\JSONText;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\FieldType\DBComposite;

class DBIcon extends DBComposite
{
    /**
     * @var string
     */
    protected $locale = null;

    protected $iconSize = null;

    protected $iconColor = null;

    /**
     * @var array<string,string>
     */
    private static $composite_db = [
        'Key' => 'Varchar(255)',
        'Data' => JSONText::class,
    ];

    private static $casting = [
        // 'getTag' => 'HTMLFragment',
    ];

    public function forTemplate(): string
    {
        return $this->getTag();
    }

    public function getTag()
    {
        $key = $this->getKey();

        if ($key) {
            $data = json_decode($this->getData(), true);

            $field = $this->scaffoldFormField($this->getName(), ['static' => true]);

            if ($field) {
                return $field->renderIconTemplate(
                    $data + [
                        'color' => $this->iconColor,
                        'size' => $this->iconSize,
                    ],
                    false,
                    $data['set'],
                    $key
                );
            }
        }
    }

    public function URL()
    {
        $key = $this->getKey();

        if ($key) {
            $data = json_decode($this->getData(), true);

            if ($data && isset($data['source'])) {
                return $data['source'] ? $data['source'] : $key;
            }
        }
    }

    public function Title()
    {
        $key = $this->getKey();

        if ($key) {
            $data = json_decode($this->getData(), true);

            if ($data && isset($data['title']) && $data['title'] && $data['title'] != '') {
                return $data['title'];
            } else {
                return $key;
            }
        }
    }

    public function Size($size)
    {
        $this->iconSize = $size;

        return $this;
    }

    public function Color($color)
    {
        $this->iconColor = $color;

        return $this;
    }

    public function getParse($key = null)
    {
        $data = $this->getData();

        if (! $data) {
            return null;
        }

        $data = json_decode($data, true);

        $parse = [
            'set' => $data['set'],
        ];

        return $key ? (isset($parse[$key]) ? $parse[$key] : null) : $parse;
    }

    public function getIconSetName()
    {
        return $this->getParse('set') ? $this->getParse('set')['name'] : null;
    }

    public function getIconType()
    {
        return $this->getParse('set') ? $this->getParse('set')['type'] : null;
    }


    public function getValue(): ?string
    {
        if (!$this->exists()) {
            return null;
        }

        return $this->getKey();
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->getField('Key');
    }

    /**
     * @param  string  $key
     * @param  bool  $markChanged
     * @return $this
     */
    public function setKey($key, $markChanged = true)
    {
        $this->setField('Key', $key, $markChanged);

        return $this;
    }

    /**
     * @return float
     */
    public function getData()
    {
        return $this->getField('Data');
    }

    /**
     * @param  mixed  $data
     * @param  bool  $markChanged
     * @return $this
     */
    public function setData($data, $markChanged = true)
    {
        // Retain nullability to mark this field as empty
        if (isset($data)) {
            $data = (float) $data;
        }
        $this->setField('Data', $data, $markChanged);

        return $this;
    }

    public function exists(): bool
    {
        return is_numeric($this->getData());
    }

    /**
     * Determine if this has a non-zero data
     *
     * @return bool
     */
    public function hasData()
    {
        $a = $this->getData();

        return ! empty($a) && is_numeric($a);
    }

    /**
     * @param  string  $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale ?: i18n::get_locale();
    }

    /**
     * Returns a CompositeField instance used as a default
     * for form scaffolding.
     *
     * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}
     *
     * @param  string  $title  Optional. Localized title of the generated instance
     * @param  array  $params
     * @return FormField
     */
    public function scaffoldFormField($title = null, $params = null): ?FormField
    {
        if ($params && isset($params['static'])) {
            $static = $params['static'];
        } else {
            $static = false;
        }

        if (! isset($params['set']['name']) && ($data = $this->getData())) {
            $params = json_decode($data, true);
            if (isset($params['set']['name'])) {
                $set = $params['set']['name'];
            }
        }

        return isset($set) ? IconField::create($set, $this->getName(), $title, '', $static) : null;
        // ->setLocale($this->getLocale());
    }
}
