@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@php
$viewProjectMemberPermission = user()->permission('view_project_members');
$viewProjectMilestonePermission = ($project->project_admin == user()->id) ? 'all' : user()->permission('view_project_milestones');
$viewTasksPermission = ($project->project_admin == user()->id) ? 'all' : user()->permission('view_project_tasks');
$viewGanttPermission = ($project->project_admin == user()->id) ? 'all' : user()->permission('view_project_gantt_chart');
$viewInvoicePermission = user()->permission('view_project_invoices');
$viewDiscussionPermission = user()->permission('view_project_discussions');
$viewNotePermission = user()->permission('view_project_note');
$viewFilesPermission = user()->permission('view_project_files');
$viewRatingPermission = user()->permission('view_project_rating');
$viewOrderPermission = user()->permission('view_project_orders');

$projectArchived = $project->trashed();
@endphp


@section('filter-section')
    <!-- FILTER START -->
    <!-- PROJECT HEADER START -->

    <div class="d-flex d-lg-block filter-box project-header bg-white">
        <div class="mobile-close-overlay w-100 h-100" id="close-client-overlay"></div>

        <div class="project-menu" id="mob-client-detail">
            <a class="d-none close-it" href="javascript:;" id="close-client-detail">
                <i class="fa fa-times"></i>
            </a>

            <nav class="tabs">
                <ul class="-primary">
                    <li>
                        <x-tab :href="route('projects.show', $project->id)" :text="__('modules.projects.overview')" class="overview" />
                    </li>

                    @if (
                        !$project->public && $viewProjectMemberPermission == 'all'
                    )
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=members'" :text="__('modules.projects.members')"
                            class="members" />
                        </li>
                    @endif

                    @if ($viewFilesPermission == 'all' || ($viewFilesPermission == 'added' && user()->id == $project->added_by) || ($viewFilesPermission == 'owned' && user()->id == $project->client_id))
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=files'" :text="__('modules.projects.files')"
                            class="files" />
                        </li>
                    @endif

                    @if ($viewProjectMilestonePermission == 'all' || $viewProjectMilestonePermission == 'added' || ($viewProjectMilestonePermission == 'owned' && user()->id == $project->client_id))
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=milestones'"
                            :text="__('modules.projects.milestones')" class="milestones" />
                        </li>
                    @endif

                    @if (in_array('tasks', user_modules()) && ($viewTasksPermission == 'all' || ($viewTasksPermission == 'added' && user()->id == $project->added_by) || ($viewTasksPermission == 'owned' && user()->id == $project->client_id)))
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=tasks'" :text="__('app.menu.tasks')" class="tasks"
                            ajax="false" />
                        </li>

                        @if (!$projectArchived)
                            <li>
                                <x-tab :href="route('projects.show', $project->id).'?tab=taskboard'" :text="__('modules.tasks.taskBoard')" class="taskboard" ajax="false" />
                            </li>

                            @if ($viewGanttPermission == 'all' || ($viewGanttPermission == 'added' && user()->id == $project->added_by) || ($viewGanttPermission == 'owned' && user()->id == $project->client_id))
                                <li>
                                    <x-tab :href="route('projects.show', $project->id).'?tab=gantt'" :text="__('modules.projects.viewGanttChart')" class="gantt" />
                                </li>
                            @endif
                        @endif
                    @endif

                    @if (in_array('invoices', user_modules()) && !is_null($project->client_id) && ($viewInvoicePermission == 'all' || ($viewInvoicePermission == 'added' && user()->id == $project->added_by) || ($viewInvoicePermission == 'owned' && user()->id == $project->client_id)))
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=invoices'" :text="__('app.menu.invoices')" class="invoices" ajax="false" />
                        </li>
                    @endif

                    @if (in_array('orders', user_modules()) && !is_null($project->client_id) && ($viewOrderPermission == 'all' || ($viewOrderPermission == 'added' && user()->id == $project->added_by) || ($viewOrderPermission == 'owned' && user()->id == $project->client_id)))
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=orders'" :text="__('app.menu.orders')" class="orders" ajax="false" />
                        </li>
                    @endif

                    @if (in_array('timelogs', user_modules()) && ($viewProjectTimelogPermission == 'all' || ($viewProjectTimelogPermission == 'added' && user()->id == $project->added_by) || ($viewProjectTimelogPermission == 'owned' && user()->id == $project->client_id)))
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=timelogs'" :text="__('app.menu.timeLogs')" class="timelogs" ajax="false" />
                        </li>
                    @endif

                    @if (in_array('expenses', user_modules()) && ($viewExpensePermission == 'all' || ($viewExpensePermission == 'added' && user()->id == $project->added_by) || ($viewExpensePermission == 'owned' && user()->id == $project->client_id)))
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=expenses'" :text="__('app.menu.expenses')" class="expenses" ajax="false" />
                        </li>
                    @endif

                    @if ($viewMiroboardPermission == 'all' && $project->enable_miroboard &&
                    ((in_array('client', user_roles()) && $project->client_access && $project->client_id == user()->id)
                    || !in_array('client', user_roles()))
                    )
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=miroboard'" :text="__('app.menu.miroboard')" class="miroboard" ajax="false" />
                        </li>
                    @endif

                    @if (in_array('payments', user_modules()) && !is_null($project->client_id) && ($viewPaymentPermission == 'all' || ($viewPaymentPermission == 'added' && user()->id == $project->added_by) || ($viewPaymentPermission == 'owned' && user()->id == $project->client_id)))
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=payments'" :text="__('app.menu.payments')" class="payments" ajax="false" />
                        </li>
                    @endif

                    @if ($viewDiscussionPermission == 'all' || ($viewDiscussionPermission == 'added' && user()->id == $project->added_by) || ($viewDiscussionPermission == 'owned' && user()->id == $project->client_id))
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=discussion'" :text="__('modules.projects.discussion')" class="discussion" ajax="false" />
                        </li>
                    @endif

                    @if ($viewNotePermission != 'none' )
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=notes'" :text="__('modules.projects.note')" class="notes" ajax="false" />
                        </li>
                    @endif

                    @if ($viewRatingPermission != 'none' && !is_null($project->client_id))
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=rating'" :text="__('modules.projects.rating')" class="rating" ajax="false" />
                        </li>
                    @endif

                    @if($viewBurndownChartPermission != 'none' || $project->project_admin == user()->id)
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=burndown-chart'"
                                :text="__('modules.projects.burndownChart')" class="burndown-chart" ajax="false" />
                        </li>
                    @endif

                    @if (!in_array('client', user_roles()))
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=activity'"
                                :text="__('modules.employees.activity')" class="activity" />
                        </li>
                    @endif

                    @if ($viewNotePermission != 'none' )
                        <li>
                            <x-tab :href="route('projects.show', $project->id).'?tab=tickets'" :text="__('app.menu.tickets')" class="tickets" ajax="false" />
                        </li>
                    @endif
                </ul>
            </nav>
        </div>

        <a class="mb-0 d-block d-lg-none text-dark-grey ml-auto mr-2 border-left-grey" onclick="openClientDetailSidebar()"><i class="fa fa-ellipsis-v "></i></a>
    </div>



    <!-- PROJECT HEADER END -->

@endsection

@section('content')

    <div class="content-wrapper pt-0 border-top-0 client-detail-wrapper">
        @include($view)
    </div>

@endsection

@push('scripts')

    <script>
        $("body").on("click", ".project-menu .ajax-tab", function(event) {
            event.preventDefault();

            $('.project-menu .p-sub-menu').removeClass('active');
            $(this).addClass('active');


            const requestUrl = this.href;

            $.easyAjax({
                url: requestUrl,
                blockUI: true,
                container: ".content-wrapper",
                historyPush: true,
                success: function(response) {
                    if (response.status == "success") {
                        $('.content-wrapper').html(response.html);
                        init('.content-wrapper');
                    }
                }
            });
        });

    </script>
    <script>
        const activeTab = "{{ $activeTab }}";
        $('.project-menu .' + activeTab).addClass('active');

    </script>
    <script>
        /*******************************************************
                 More btn in projects menu Start
        *******************************************************/

        const container = document.querySelector('.tabs');
        const primary = container.querySelector('.-primary');
        const primaryItems = container.querySelectorAll('.-primary > li:not(.-more)');
        container.classList.add('--jsfied'); // insert "more" button and duplicate the list

        primary.insertAdjacentHTML('beforeend', `
        <li class="-more">
            <button type="button" class="px-4 h-100 bg-grey d-none d-lg-flex align-items-center" aria-haspopup="true" aria-expanded="false">
            {{__('app.more')}} <span>&darr;</span>
            </button>
            <ul class="-secondary" id="hide-project-menues">
            ${primary.innerHTML}
            </ul>
        </li>
        `);
        const secondary = container.querySelector('.-secondary');
        const secondaryItems = secondary.querySelectorAll('li');
        const allItems = container.querySelectorAll('li');
        const moreLi = primary.querySelector('.-more');
        const moreBtn = moreLi.querySelector('button');
        moreBtn.addEventListener('click', e => {
            e.preventDefault();
            container.classList.toggle('--show-secondary');
            moreBtn.setAttribute('aria-expanded', container.classList.contains('--show-secondary'));
        }); // adapt tabs

        const doAdapt = () => {
            // reveal all items for the calculation
            allItems.forEach(item => {
                item.classList.remove('--hidden');
            }); // hide items that won't fit in the Primary

            let stopWidth = moreBtn.offsetWidth;
            let hiddenItems = [];
            const primaryWidth = primary.offsetWidth;
            primaryItems.forEach((item, i) => {
                if (primaryWidth >= stopWidth + item.offsetWidth) {
                    stopWidth += item.offsetWidth;
                } else {
                    item.classList.add('--hidden');
                    hiddenItems.push(i);
                }
            }); // toggle the visibility of More button and items in Secondary

            if (!hiddenItems.length) {
                moreLi.classList.add('--hidden');
                container.classList.remove('--show-secondary');
                moreBtn.setAttribute('aria-expanded', false);
            } else {
                secondaryItems.forEach((item, i) => {
                    if (!hiddenItems.includes(i)) {
                        item.classList.add('--hidden');
                    }
                });
            }
        };

        doAdapt(); // adapt immediately on load

        window.addEventListener('resize', doAdapt); // adapt on window resize
        // hide Secondary on the outside click

        document.addEventListener('click', e => {
            let el = e.target;

            while (el) {
                if (el === secondary || el === moreBtn) {
                    return;
                }

                el = el.parentNode;
            }

            container.classList.remove('--show-secondary');
            moreBtn.setAttribute('aria-expanded', false);
        });
        /*******************************************************
                 More btn in projects menu End
        *******************************************************/
    </script>
@endpush
