<?php

namespace Antto\LogViewer\Http\Controllers;

use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Antto\LogViewer\Services\LogViewer;
use Antto\LogViewer\Repositories\LogFile;
use Dcat\Admin\Http\Controllers\AdminController;
use Antto\LogViewer\DcatLogViewerServiceProvider;

class DcatLogViewerController extends AdminController
{
    /**
     * Get title.
     *
     * @return string
     */
    protected function title()
    {
        return DcatLogViewerServiceProvider::trans('log-viewer.title');
    }

    /**
     * List page
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        $logViewer = new LogViewer(request('file'));
        $data = $logViewer->fetch(request('offset', 0));

        return $content
            ->breadcrumb($this->title())
            ->title($this->title())
            ->description("{$logViewer->getFilePath()} ( {$logViewer->getFiletime()} [{$logViewer->getFilesizeHuman()}] )")
            ->body(function (Row $row) use ($logViewer, $data) {
                $row->column(10, $this->fileContentList($logViewer, $data));
                $row->column(2, function (Column $column) use ($logViewer) {
                    $list = $logViewer->getLogFiles();
                    $column->append(view('antto.dcat-log-viewer::log-list', compact('list')));
                });
            });
    }

    /**
     * Build file content list.
     *
     * @param string $file
     *
     * @return Grid
     */
    protected function fileContentList($logViewer, $data)
    {
        $grid = Grid::make(new LogFile($data), function (Grid $grid) use ($logViewer) {
            $grid->column('time')->width(200);
            $grid->column('env')->width(100)->label();
            $grid->column('level')
                ->width(130)
                ->label([
                    'EMERGENCY' => 'black',
                    'ALERT'     => 'indigo',
                    'CRITICAL'  => 'pink',
                    'ERROR'     => 'danger',
                    'WARNING'   => 'warning',
                    'NOTICE'    => 'cyan',
                    'INFO'      => 'info',
                    'DEBUG'     => 'green',
                ]);
            $grid->column('info')
                ->modal(function (Grid\Displayers\Modal $modal) {
                    $modal->icon('fa-info-circle');
                    $modal->xl();
                    if ($this->trace == '') {
                        return '';
                    }
                    return "<textarea class='form-control' style='height:70vh;' disabled>$this->trace</textarea>";
                });

            $file = $logViewer->getFile();

            $grid->tools('<a class="btn btn-info" href="' . admin_route('dcat-log-viewer.index', compact('file')) . '">刷新</a>');

            $grid->tools(view('antto.dcat-log-viewer::pagination-btn', [
                'file' => $file,
                'offset' => $logViewer->getNextPageOffset(),
                'content' => '下一页'
            ]));

            $grid->tools(view('antto.dcat-log-viewer::pagination-btn', [
                'file' => $file,
                'offset' => $logViewer->getPrevPageOffset(),
                'content' => '上一页'
            ]));

            $grid->disableRefreshButton();
            $grid->disablePagination();
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->disableActions();
            $grid->disableFilterButton();
        });

        return $grid->render();
    }
}
