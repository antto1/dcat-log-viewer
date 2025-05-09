<?php

namespace Antto\LogViewer\Services;

/**
 * Class LogViewer.
 */
class LogViewer
{
    /**
     * The log file name.
     *
     * @var string
     */
    public $file;

    /**
     * The path of log file.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Start and end offset in current page.
     *
     * @var array
     */
    protected $pageOffset = ['start' => null, 'end' => null];

    /**
     * LogViewer constructor.
     *
     * @param null $file
     */
    public function __construct($file = null)
    {
        if (is_null($file)) {
            $file = $this->getLastModifiedLog();
        }

        $this->file = $file;

        $this->getFilePath();
    }

    /**
     *  Get file
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Get file path by giving log file name.
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getFilePath()
    {
        if (!$this->filePath) {
            $path = sprintf(storage_path('logs/%s'), $this->file);

            if (!file_exists($path)) {
                // throw new \Exception('log not exists!');
                // create log file if not exists
                touch($path);
            }

            $this->filePath = $path;
        }

        return $this->filePath;
    }

    /**
     * Get size of log file.
     *
     * @return int
     */
    public function getFilesize()
    {
        return filesize($this->filePath);
    }

    /**
     * Get time of log file.
     *
     * @return int
     */
    public function getFiletime()
    {
        return date('Y-m-d H:i:s', filectime($this->filePath));
    }

    /**
     * Get size of log file.
     *
     * @return int
     */
    public function getFilesizeHuman()
    {
        return $this->bytesToHuman($this->getFilesize());
    }

    /**
     * Get log file list in storage.
     *
     * @param int $count
     *
     * @return array
     */
    public function getLogFiles($count = 20)
    {
        $files = glob(storage_path('logs/*'));
        $files = array_combine($files, array_map('filemtime', $files));
        arsort($files);

        $files = array_map('basename', array_keys($files));

        return array_slice($files, 0, $count);
    }

    /**
     * Get the last modified log file.
     *
     * @return string
     */
    public function getLastModifiedLog()
    {
        $logs = $this->getLogFiles();

        return current($logs);
    }

    /**
     * Get offset
     *
     * @return array
     */
    public function getOffset()
    {
        return [
            'start' => $this->getPrevPageOffset(),
            'end'   => $this->getNextPageOffset(),
        ];
    }

    /**
     * Get prev page offset
     *
     * @return array
     */
    public function getPrevPageOffset()
    {
        if ($this->pageOffset['end'] >= $this->getFilesize() - 1) {
            return null;
        }

        return $this->pageOffset['end'];
    }

    /**
     * Get next page offset
     *
     * @return array
     */
    public function getNextPageOffset()
    {
        if ($this->pageOffset['start'] == 0) {
            return null;
        }

        return -$this->pageOffset['start'];
    }

    /**
     * Fetch logs by giving offset.
     *
     * @param int $seek
     * @param int $lines
     * @param int $buffer
     *
     * @return array
     *
     * @see http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
     */
    public function fetch($seek = 0, $lines = 20, $buffer = 4096)
    {
        $f = fopen($this->filePath, 'rb');

        if ($seek) {
            fseek($f, abs($seek));
        } else {
            fseek($f, 0, SEEK_END);
        }

        if (fread($f, 1) != "\n") {
            $lines -= 1;
        }
        fseek($f, -1, SEEK_CUR);

        // 从前往后读,上一页
        // Start reading
        if ($seek > 0) {
            $output = '';

            $this->pageOffset['start'] = ftell($f);

            while (!feof($f) && $lines >= 0) {
                $output = $output . ($chunk = fread($f, $buffer));
                $lines -= substr_count($chunk, "\n[20");
            }

            $this->pageOffset['end'] = ftell($f);

            while ($lines++ < 0) {
                $strpos = strrpos($output, "\n[20") + 1;
                $_ = mb_strlen($output, '8bit') - $strpos;
                $output = substr($output, 0, $strpos);
                $this->pageOffset['end'] -= $_;
            }

            // 从后往前读,下一页
        } else {
            $output = '';

            $this->pageOffset['end'] = ftell($f);

            while (ftell($f) > 0 && $lines >= 0) {
                $offset = min(ftell($f), $buffer);
                fseek($f, -$offset, SEEK_CUR);
                $output = ($chunk = fread($f, $offset)) . $output;
                fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
                $lines -= substr_count($chunk, "\n[20");
            }

            $this->pageOffset['start'] = ftell($f);

            while ($lines++ < 0) {
                $strpos = strpos($output, "\n[20") + 1;
                $output = substr($output, $strpos);
                $this->pageOffset['start'] += $strpos;
            }
        }

        fclose($f);

        return $this->parseLog($output);
    }

    /**
     * Get tail logs in log file.
     *
     * @param int $seek
     *
     * @return array
     */
    public function tail($seek)
    {
        // Open the file
        $f = fopen($this->filePath, 'rb');

        if (!$seek) {
            // Jump to last character
            fseek($f, -1, SEEK_END);
        } else {
            fseek($f, abs($seek));
        }

        $output = '';

        while (!feof($f)) {
            $output .= fread($f, 4096);
        }

        $pos = ftell($f);

        fclose($f);

        $logs = [];

        foreach ($this->parseLog(trim($output)) as $log) {
            $logs[] = $this->renderTableRow($log);
        }

        return [$pos, $logs];
    }

    /**
     * Parse raw log text to array.
     *
     * @param $raw
     *
     * @return array
     */
    protected function parseLog($raw)
    {
        $logs = preg_split('/\[(\d{4}(?:-\d{2}){2} \d{2}(?::\d{2}){2})\] (\w+)\.(\w+):((?:(?!{"exception").)*)?/', trim($raw), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        foreach ($logs as $index => $log) {
            if (preg_match('/^\d{4}/', $log)) {
                break;
            } else {
                unset($logs[$index]);
            }
        }

        if (empty($logs)) {
            return [];
        }

        $parsed = [];

        foreach (array_chunk($logs, 5) as $log) {
            $parsed[] = [
                'time'  => $log[0] ?? '',
                'env'   => $log[1] ?? '',
                'level' => $log[2] ?? '',
                'info'  => $log[3] ?? '',
                'trace' => trim($log[4] ?? ''),
            ];
        }

        unset($logs);

        rsort($parsed);

        return $parsed;
    }

    public function bytesToHuman($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
