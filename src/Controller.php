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

    public function show(Directory $directory, SiteContent $document, SiteContent $folder = null)
    {
        if ($document->id) {
            $config = $directory->getConfig($document->id);
            $items  = $directory->getResources($folder ?? $document, $config);

            return view('directory::list', [
                'document' => $document,
                'folder'   => $folder,
                'items'    => $items,
                'config'   => $config,
                'lang'     => $config['lang'],
            ]);
        }
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
