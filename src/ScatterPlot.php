<?php

namespace App;

class ScatterPlot
{
    protected string $file;

    public function __construct($file, protected array $bananaData = [])
    {
        if (!file_exists($file)) {
            echo basename($file) . ' does not exist..';
            return;
        }

        $this->file = $file;
    }

    public function handle()
    {
        // Convert the CSV into an array:
        $handle = fopen($this->file, 'r');
        // remove the CSV header:
        $header = fgetcsv($handle);
        while (($row = fgetcsv($header)) !== false) {
            $this->bananaData[] = [
                'x' => intval($row[0]),
                'y' => intval($row[1])
            ];
        }
        fclose($handle);

        if (empty($this->bananaData)) {
            echo 'This CSV is empty..';
        }
    }
}