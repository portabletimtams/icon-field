<?php

namespace Goldfinch\IconField\Commands;

use Goldfinch\Taz\Console\GeneratorCommand;
use Goldfinch\Taz\Services\Templater;

#[AsCommand(name: 'vendor:icon-field:templates')]
class IconTemplatesCommand extends GeneratorCommand
{
    protected static $defaultName = 'vendor:icon-field:templates';

    protected $description = 'Publish [goldfinch/icon-field] templates';

    protected $no_arguments = true;

    protected function execute($input, $output): int
    {
        $templater = Templater::create($input, $output, $this, 'goldfinch/icon-field');

        $theme = $templater->defineTheme();

        if (is_string($theme)) {
            $componentPathTemplates = BASE_PATH.'/vendor/goldfinch/icon-field/templates/';
            $componentPath = $componentPathTemplates.'Goldfinch/IconField/';
            $themeTemplates = 'themes/'.$theme.'/templates/';
            $themePath = $themeTemplates.'Goldfinch/IconField/';

            $files = [
                [
                    'from' => $componentPath.'Types/DirItem.ss',
                    'to' => $themePath.'Types/DirItem.ss',
                ],
                [
                    'from' => $componentPath.'Types/FontItem.ss',
                    'to' => $themePath.'Types/FontItem.ss',
                ],
                [
                    'from' => $componentPath.'Types/JsonItem.ss',
                    'to' => $themePath.'Types/JsonItem.ss',
                ],
                [
                    'from' => $componentPath.'Types/UploadItem.ss',
                    'to' => $themePath.'Types/UploadItem.ss',
                ],
            ];

            return $templater->copyFiles($files);
        } else {
            return $theme;
        }
    }
}
