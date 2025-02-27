<?php

namespace App;

class ScatterPlot
{
    protected \GdImage $scatterPlot;

    public function __construct(
        protected string $file,
        protected array $data = [],
        protected int $width = 600,
        protected int $height = 600,
        protected int $margin = 50
    ) {
        if (!file_exists($file)) {
            echo basename($file) . ' does not exist..';
            return;
        }

        $this->file = $file;
    }

    public function handle(): ScatterPlot
    {
        $handle = fopen($this->file, 'r');
        // remove the CSV header:
        $header = fgetcsv($handle);
        // Convert the CSV into an array:
        while (($row = fgetcsv($handle)) !== false) {
            $this->data[] = [
                'x' => intval($row[0]),
                'y' => intval($row[1])
            ];
        }
        fclose($handle);

        if (empty($this->data)) {
            echo 'This CSV is empty..';
        }

        return $this;
    }

    public function createScatterPlot(): ScatterPlot
    {
        // returns GdImage, initially with black background:
        $this->scatterPlot = imagecreatetruecolor($this->width, $this->height);

        $black = imagecolorallocate($this->scatterPlot, 0, 0, 0);
        $offWhite = imagecolorallocate($this->scatterPlot, 237, 235, 233); //#e0dedc
        $yellow = imagecolorallocate($this->scatterPlot, 255, 200, 45);
        // Change GdImage's background:
        imagefill($this->scatterPlot, 0, 0, $offWhite);

        // The coordinate system for GD considering the margin is:
        //  top left: 50,50
        //  bottom right: 550,550
        $x1 = $this->margin;
        $y1 = $this->height - $this->margin;
        $x2 = $this->width - $this->margin;
        $y2 = $this->height - $this->margin;

        // draw the x-axis:
        imageline($this->scatterPlot, $x1, $y1, $x2, $y2, $black);
        // draw the y-axis:
        imageline($this->scatterPlot, $x1, $y1, $this->margin, $this->margin, $black);

        // get min and max values for $x and $y for the scatter plot:
        $xValues = array_column($this->data, 'x');
        $yValues = array_column($this->data, 'y');
        $xMin = min($xValues);
        $xMax = max($xValues);
        $yMin = min($yValues);
        $yMax = max($yValues);

        // Draw the scatter plot, one dot at a time:
        foreach ($this->data as $dot) {
            $normalizedX = ($dot['x'] - $xMin) / ($xMax - $xMin);
            $totalHorizontalSpace = $this->width - ($this->margin * 2);
            $centerX = $this->margin + ($normalizedX * $totalHorizontalSpace);

            $normalizedY = ($dot['y'] - $yMin) / ($yMax - $yMin);
            $totalVerticalSpace = $this->height - ($this->margin * 2);
            $centerY = ($this->height - $this->margin) - ($normalizedY * $totalVerticalSpace);

            $widthOfEllipse = $heightOfEllipse = 3;
            $centerX = intval($centerX);
            $centerY = intval($centerY);
            imagefilledellipse($this->scatterPlot, $centerX, $centerY, $widthOfEllipse, $heightOfEllipse, $yellow);
        }

        return $this;
    }

    public function printScatterPlotToClient(): void
    {
        // send out the image (no need to delete it from mem since PHP8):
        header("Content-Type: image/png");
        imagepng($this->scatterPlot);
    }
}