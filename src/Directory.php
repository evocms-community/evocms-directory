<?php

namespace EvolutionCMS\Directory;

use EvolutionCMS\Models\SiteContent;

class Directory
{
    private $configs;

    public function getConfigs()
    {
        if ($this->configs !== null) {
            return $this->configs;
        }

        $configs = [];

        foreach (glob(EVO_CORE_PATH . 'custom/directory/*.php') as $entry) {
            $config = include($entry);

            if (is_array($config) && isset($config['ids'])) {
                foreach ($config['ids'] as $id) {
                    $configs[$id] = $config;
                }
            }
        }

        return $this->configs = $configs;
    }

    public function getConfig($id)
    {
        $configs = $this->getConfigs();

        if (isset($configs[$id])) {
            $config = $configs[$id];
            $default = $this->getDefaultConfig();
            $config['columns'] = array_merge($default['columns'], $config['columns'] ?? []);
            $config = array_merge($default, $config);
            $config['lang'] = array_merge(__('directory::messages'), $config['lang']);

            $sort = 0;
            $config['columns'] = array_map(function($column) use (&$sort) {
                if (!isset($column['order'])) {
                    $column['order'] = $sort++;
                }
                return $column;
            }, $config['columns']);

            uasort($config['columns'], function($a, $b) {
                return $a['sort'] - $b['sort'];
            });

            $config['id'] = $id;

            return $config;
        }

        return null;
    }

    public function getResources(SiteContent $parent, array $config)
    {
        $items = $parent->children()
            ->withTVs(array_keys($config['columns']))
            ->when(isset($config['query']), $config['query'])
            ->orderBy('isfolder', 'desc')
            ->orderBy('menuindex')
            ->paginate(20);
            /*->map(function($item) use ($config) {
                if (isset($config['prepare'])) {
                    $item = call_user_func($config['prepare'], $item);
                }

                return $item;
            });*/

        return $items;
    }

    public function actionPublish($resources)
    {
        $resources->update(['published' => 1]);
    }

    public function actionUnpublish($resources)
    {
        $resources->update(['published' => 0]);
    }

    public function actionDelete($resources)
    {
        $resources->update(['deleted' => 1]);
    }

    public function actionRestore($resources)
    {
        $resources->update(['deleted' => 0]);
    }

    public function actionDuplicate($resources)
    {

    }

    private function getDefaultConfig()
    {
        return [
            'show_actions' => true,

            'actions' => [
                'publish',
                'unpublish',
                'delete',
                'restore',
                'duplicate',
            ],

            'columns' => [
                'pagetitle' => [
                    'caption' => __('directory::messages.pagetitle'),
                    'sort' => 0,
                    'renderer' => function($value, $row, $config) {
                        if ($row->isfolder) {
                            return '
                                <i class="fa fa-folder"></i>
                                <a href="' . route('directory::show', ['document' => $config['id'], 'folder' => $row->id]) . '">' . $row->pagetitle . '</a>
                            ';
                        } else {
                            return '
                                <i class="fa fa-file-o"></i>
                                <a href="index.php?a=27&id=' . $row->id . '" title="' . $config['lang']['edit_document'] . '" target="main">' . $row->pagetitle . '</a>
                            ';
                        }
                    }
                ],
            ],

            'limits' => [
                10, 25, 50, 100
            ],

            'default_limit' => 25,
        ];
    }
}
