<?php

Event::listen('evolution.OnManagerNodePrerender', function($params) {
    $configs = evo()->directory->getConfigs();

    if (isset($configs[ $params['ph']['id'] ])) {
        $params['ph']['tree_page_click'] = route('directory::show', $params['ph']['id']);
        $params['ph']['icon'] = '<i class="fa fa-list-alt"></i>';
        $params['ph']['icon_folder_open'] = "<i class='fa fa-list-alt'></i>";
        $params['ph']['icon_folder_close'] = "<i class='fa fa-list-alt'></i>";
        $params['ph']['showChildren'] = '0';
    }

    return serialize($params['ph']);
});
