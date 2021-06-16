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
            $items   = $directory->getResources($current, $config);

            return view('directory::list', [
                'container' => $container,
                'folder'    => $folder,
                'crumbs'    => $this->getCrumbs($current, $container),
                'items'     => $items,
                'config'    => $config,
                'lang'      => $config['lang'],
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
