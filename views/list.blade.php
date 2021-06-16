@extends('directory::layout')

@section('pagetitle', $folder ? $folder->pagetitle : $container->pagetitle)

@section('buttons')
    <div id="actions">
        <div class="btn-group">
            <a class="btn btn-success" href="index.php?a=4&pid={{ $container->id }}">
                <i class="fa fa-file-o"></i><span>{{ $lang['create_child'] }}</span>
            </a>
            <a href="javascript:;" class="btn btn-secondary" onclick="location.reload();">
                <i class="fa fa-refresh"></i><span>@lang('directory::messages.refresh')</span>
            </a>
            <a class="btn btn-secondary" href="index.php?a=27&id={{ $container->id }}">
                <i class="fa fa-pencil"></i><span>{{ $lang['edit_document'] }}</span>
            </a>
        </div>
    </div>
@endsection

@if (!empty($crumbs))
    @section('breadcrumbs')
        <div class="crumbs">
            <ul>
                @foreach ($crumbs as $crumb)
                    @if ($loop->last)
                        <li class="current-crumb">
                            {{ $crumb->pagetitle }}
                    @else
                        <li class="crumb">
                            <a href="{{ route('directory::show', ['container' => $config['id'], 'folder' => $crumb->id != $container->id ? $crumb->id : null]) }}">
                                @if ($loop->first)
                                    <i class="fa fa-home"></i>
                                @else
                                    {{ $crumb->pagetitle }}
                                @endif
                            </a>
                    @endif
                @endforeach
            </ul>
        </div>
    @endsection
@endif

@section('body')
    <div class="tab-page" id="tab_main">
        <h2 class="tab">
            {{ $lang['documents_list'] }}
        </h2>

        <script type="text/javascript">
            tpModule.addTabPage(document.getElementById('tab_main'));
        </script>

        <form method="get">
            <div class="row">
                <table class="table data">
                    @if ($items->count())
                        <thead>
                            <tr>
                                <td style="width: 1%;">
                                    <input type="checkbox" class="check-all" title="{{ $lang['select_all'] }}">
                                </td>
                                <td style="width: 1%;"></td>
                                @foreach ($config['columns'] as $column)
                                    <td class="{{ $column['class'] ?? '' }}" {!! $column['attrs'] ?? '' !!}>
                                        {{ $column['caption'] ?? '' }}
                                    </td>
                                @endforeach
                            </tr>
                        </thead>
                    @endif

                    <tbody class="directory-list">
                        @if ($folder)
                            <tr data-published="{{ $item->published }}" data-deleted="{{ $item->deleted }}" data-isfolder="{{ $item->isfolder }}">
                                <td></td>
                                <td></td>

                                @foreach ($config['columns'] as $key => $column)
                                    <td class="{{ $key }}-column {{ $column['class'] ?? '' }}" {!! $column['attrs'] ?? '' !!}>
                                        @if ($key == 'pagetitle')
                                            <i class="fa fa-level-up"></i>
                                            <a href="{{ route('directory::show', ['container' => $config['id'], 'folder' => $folder->parent != $container->id ? $folder->parent : null]) }}">
                                                ...
                                            </a>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endif

                        @forelse ($items as $item)
                            <tr class="{{ $item->deleted ? 'item-deleted' : ''}} {{ !$item->published ? 'item-unpublished' : ''}} {{ $item->hidemenu ? 'item-hidden' : ''}}" data-published="{{ $item->published }}" data-deleted="{{ $item->deleted }}" data-isfolder="{{ $item->isfolder }}" id="node{{ $item->id }}">
                                <td data-published="{{ $item->published }}" data-deleted="{{ $item->deleted }}" data-isfolder="{{ $item->isfolder }}" data-href="{{ url($item->id) }}"><input type="checkbox" name="selected[]" value="{{ $item->id }}"></td>
                                <td class="toggle-item-menu" onclick="directory.showMenu(event, {{ $item->id }},'{{ $item->pagetitle }}');" oncontextmenu="this.onclick(); return false;"><span class="fa fa-bars"></span></td>

                                @foreach ($config['columns'] as $key => $column)
                                    <td class="{{ $key }}-column {{ $column['class'] ?? '' }}" {!! $column['attrs'] ?? '' !!}>
                                        @if ($column['renderer'])
                                            {!! call_user_func($column['renderer'], $item->getAttribute($key), $item, $config) !!}
                                        @else
                                            {{ $item->getAttribute($key) }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center">
                                    {{ $lang['no_items'] }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($items->count())
                    <div class="table-footer flex">
                        <div class="selected-control">
                            <label>
                                <input type="checkbox" class="check-all" title="{{ $lang['select_all'] }}"> {{ $lang['select_all'] }}
                            </label>
                        </div>

                        @if ($items->total() > $items->count())
                            <div class="selected-control">
                                <label>
                                    <input type="checkbox" name="selected" value="all"> @lang('directory::messages.select_whole_list', [
                                        'total' => $items->total(),
                                    ])
                                </label>
                            </div>
                        @endif

                        @if ($config['show_actions'] && !empty($config['actions']))
                            <div class="list-actions">
                                <select name="action" class="form-control">
                                    <option value="">-- {{ $lang['choose_action'] }} --</option>
                                    @foreach ($config['actions'] as $action)
                                        <option value="{{ $action }}">{{ $lang['action_' . $action] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="list-summary">
                            @lang('directory::messages.shown_from', [
                                'count' => $items->count(),
                                'total' => $items->total(),
                            ])
                        </div>

                        {{ $items->links('directory::links') }}

                        <div class="limits">
                            <select name="limit" class="form-control">
                                @foreach ($config['limits'] as $limit)
                                    <option @if ($currentLimit == $limit) selected @endif>{{ $limit }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>

            <input type="hidden" name="folder_id" value="{{ $folder ? $folder->id : $container->id }}">
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        var directory = {
            init: function() {
                this.initChecks();

                if (!parent.modx.tree.hackedDirectory) {
                    this.originalMenuHandler = parent.modx.tree.menuHandler;
                    this.originalRestoreTree = parent.modx.tree.restoreTree;
                    parent.modx.tree.hackedDirectory = true;

                    parent.modx.tree.menuHandler = function(action) {
                        parent.modx.tree.restoreTree = function() {
                            directory.originalRestoreTree.call(parent.modx.tree);
                            parent.modx.tree.restoreTree = directory.originalRestoreTree;

                            if ([7,9,10,4,8].indexOf(action) !== -1) {
                                location.reload();
                            }
                        };

                        directory.originalMenuHandler.call(parent.modx.tree, action);
                    };
                }

                $('.list-actions select').change(function() {
                    if (this.value == '') {
                        return;
                    }

                    parent.modx.main.work();

                    $.post("{{ route('directory::action') }}", $(this.form).serialize(), function(response) {
                        location.reload();
                    });
                });

                $('.limits select').on('change', function() {
                    $.post("{{ route('directory::limit', $container->id) }}", {limit: $(this).val()}, function(response) {
                        location.reload();
                    });
                });
            },

            initChecks: function() {
                var $checks = $('.directory-list input[type="checkbox"]'),
                    $pageCheck = $('.check-all'),
                    $listCheck = $('[name="selected"][value="all"]');

                $pageCheck.click(function(e) {
                    $checks.each(function() {
                        this.checked = !this.checked;
                    });

                    $pageCheck.not(this).get(0).checked = this.checked;

                    if ($listCheck.length) {
                        $listCheck.get(0).checked = false;
                    }
                });

                $listCheck.change(function(e) {
                    var selfChecked = this.checked;

                    $checks.each(function() {
                        this.checked = selfChecked;
                    });

                    $pageCheck.each(function() {
                        this.checked = selfChecked;
                    });
                });

                $checks.change(function() {
                    if ($listCheck.length) {
                        $listCheck.get(0).checked = false;
                    }

                    var allChecked = true;

                    $checks.each(function() {
                        if (!this.checked) {
                            allChecked = false;
                            return false;
                        }
                    });

                    $pageCheck.each(function() {
                        this.checked = allChecked;
                    });
                });
            },

            showMenu: function(e, id, title) {
                if (e.ctrlKey) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                var body = parent.document.getElementById('frameset'),
                    el = e.currentTarget,
                    row = e.currentTarget.parentElement,
                    x = 0,
                    y = 0;

                if (el) {
                    var menu = parent.document.getElementById('mx_contextmenu');
                    e.target.dataset.toggle = '#mx_contextmenu';
                    parent.modx.hideDropDown(e);

                    var i4 = parent.document.getElementById('item4'),
                        i5 = parent.document.getElementById('item5'),
                        i8 = parent.document.getElementById('item8'),
                        i9 = parent.document.getElementById('item9'),
                        i10 = parent.document.getElementById('item10'),
                        i11 = parent.document.getElementById('item11');

                    if (parent.modx.permission.publish_document === 1) {
                        i9.style.display = 'block';
                        i10.style.display = 'block';
                        if (parseInt(row.dataset.published) === 1) {
                            i9.style.display = 'none';
                        } else {
                            i10.style.display = 'none';
                        }
                    } else if (i5) {
                        i5.style.display = 'none';
                    }

                    if (parent.modx.permission.delete_document === 1) {
                        i4.style.display = 'block';
                        i8.style.display = 'block';
                        if (parseInt(row.dataset.deleted) === 1) {
                            i4.style.display = 'none';
                            i9.style.display = 'none';
                            i10.style.display = 'none';
                        } else {
                            i8.style.display = 'none';
                        }
                    }
                    if (i11) {
                        if (parseInt(row.dataset.isfolder) === 1) {
                            i11.style.display = 'block';
                        } else {
                            i11.style.display = 'none';
                        }
                    }
                    var bodyHeight = body.offsetHeight + body.offsetTop;
                    x = e.clientX > 0 ? e.clientX : e.pageX;
                    y = e.clientY > 0 ? e.clientY : e.pageY;
                    if (y + menu.offsetHeight / 2 > bodyHeight) {
                        y = bodyHeight - menu.offsetHeight - 5;
                    } else if (y - menu.offsetHeight / 2 < body.offsetTop) {
                        y = body.offsetTop + 5;
                    } else {
                        y = y - menu.offsetHeight / 2;
                    }

                    x += parent.document.getElementById('main').offsetLeft;

                    if (title.length > 30) {
                        title = title.substr(0, 30) + '...';
                    }
                    var f = parent.document.getElementById('nameHolder');
                    f.innerHTML = title;
                    menu.style.left = x + (parent.modx.config.textdir === 'rtl' ? '-190' : '') + 'px';
                    menu.style.top = y + 'px';
                    menu.classList.add('show');

                    parent.modx.tree.itemToChange = id;
                }
            }
        };

        directory.init();
    </script>
@endpush
