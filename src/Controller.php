<?php

namespace EvolutionCMS\Directory;

use EvolutionCMS\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class Controller
{
    public function index(Request $request)
    {
        return view('directory::index', [
        ]);
    }

    public function show(Directory $directory, SiteContent $container, SiteContent $folder = null)
    {
        if ($container->id) {
            $config  = $directory->getConfig($container->id);
            $current = $folder ?? $container;
            $sessionKey = 'directory::limit.' . $container->id;
            $limit   = session()->get($sessionKey, $config['default_limit']);
            $items   = $directory->getResources($current, $config, $limit);

            return view('directory::list', [
                'container' => $container,
                'folder'    => $folder,
                'crumbs'    => $this->getCrumbs($current, $container),
                'items'     => $items,
                'config'    => $config,
                'lang'      => $config['lang'],
                'currentLimit' => $limit,
            ]);
        }
    }

    protected function getCrumbs(SiteContent $folder, SiteContent $container)
    {
        if ($container == $folder) {
            return [];
        }

        $parents = [];

        foreach (evo()->getParentIds($folder->id) as $id) {
            $parents[] = $id;

            if ($id == $container->id) {
                break;
            }
        }

        $result = SiteContent::whereIn('id', $parents)->get();
        $result->push($folder);

        return $result;
    }

    public function setLimit(Request $request, SiteContent $container, Directory $directory)
    {
        $limit = $request->input('limit');

        if ($container->id) {
            $config = $directory->getConfig($container->id);

            if (is_numeric($limit) && in_array($limit, $config['limits'])) {
                $sessionKey = 'directory::limit.' . $container->id;
                session()->put($sessionKey, $limit);
                return response()->json(['status' => 'success']);
            }
        }

        return response()->json(['status' => 'failed']);
    }

    public function action(Request $request, Directory $directory)
    {
        $action   = $request->input('action');
        $selected = $request->input('selected');

        if (is_string($action) && !empty($selected)) {
            $action = 'action' . ucfirst($action);

            if (method_exists($directory, $action)) {
                $resources = SiteContent::query()
                    ->when(is_array($selected), function($query) use ($selected) {
                        return $query->whereIn('id', $selected);
                    })
                    ->when($selected == 'all', function($query) use ($request) {
                        return $query->where('parent', $request->input('folder_id'));
                    });

                call_user_func([$directory, $action], $resources);

                return response()->json(['status' => 'success']);
            }
        }

        return response()->json(['status' => 'failed']);
    }
}
