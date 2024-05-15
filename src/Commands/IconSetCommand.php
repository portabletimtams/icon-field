<?php

namespace Goldfinch\IconField\Commands;

use Goldfinch\Taz\Services\InputOutput;
use Goldfinch\Taz\Console\GeneratorCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Question\ChoiceQuestion;

#[AsCommand(name: 'iconset')]
class IconSetCommand extends GeneratorCommand
{
    protected static $defaultName = 'iconset';

    protected $description = 'Add new icon set';

    protected $no_arguments = true;

    protected function execute($input, $output): int
    {
        $setName = $this->askClassNameQuestion('Name of the set? (eg: font_awesome, primary_set)', $input, $output);

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'What type of source this set is going to use?',
            ['font', 'dir', 'upload', 'json'],
        );
        $question->setErrorMessage('The selection %s is invalid.');
        $setType = $helper->ask($input, $output, $question);

        if ($setType == 'font') {
            $sourceExample = '(eg: https://cdn.myicons.net/icons.min.css)';
        } else if ($setType == 'dir') {
            $sourceExample = '(within the public dir, eg: assets/icons)';
        } else if ($setType == 'upload') {
            $sourceExample = '(eg: icons)';
        }

        if ($setType == 'json') {
            $source = 'icon-' . $setName . '.json';
        } else {
            $source = $this->askStringQuestion('Specify the source for this set ' . $sourceExample, $input, $output);
        }

        $setOptions = [
            'vector' => true,
            'type' => $setType,
            'source' => $source,
        ];

        // find config
        $config = $this->findYamlConfigFileByName('app-icons');

        // create new config if not exists
        if (!$config) {

            $command = $this->getApplication()->find('make:config');
            $command->run(new ArrayInput([
                'name' => 'icons',
                '--plain' => true,
                '--after' => 'goldfinch/icon-field',
                '--nameprefix' => 'app-',
            ]), $output);

            $config = $this->findYamlConfigFileByName('app-icons');
        }

        // update config
        $this->updateYamlConfig(
            $config,
            'Goldfinch\IconField\Forms\IconField' . '.icons_sets.' . $setName,
            $setOptions,
        );

        $config = $this->findYamlConfigFileByName('app-icons');

        $fs = new Filesystem();

        if ($setType == 'font' || $setType == 'json') { //  || $setType == 'dir'

            if ($setType == 'json') {
                $schemaTemplate = 'schema-json.json';
            } else {
                $schemaTemplate = 'schema.json';
            }

            $fs->copy(
                BASE_PATH .
                    '/vendor/goldfinch/icon-field/components/' . $schemaTemplate,
                'app/_schema/icon-'.$setName.'.json',
            );
        }

        if ($setType == 'dir') {

            $path = PUBLIC_PATH . '/' . $source;

            if (!$fs->exists($path)) {

                $createSource = $this->askStringQuestion('The folder `'.$path.'` does not exist. Would you like to create it? [y/n]', $input, $output, 'y');

                if ($createSource == 'y' || $createSource == 'Y') {
                    $fs->mkdir($path);
                }
            }

        } else if ($setType == 'upload') {
            $path = ASSETS_PATH . '/' . $source;

            if (!$fs->exists($path)) {
                $io = new InputOutput($input, $output);
                $io->info('Youn need to create `'.$source.'` dir in `'.ASSETS_DIR.'` through CMS (/admin/assets). Make sure your uploaded icons in this folder are published.');
            }
        }

        return Command::SUCCESS;
    }
}
