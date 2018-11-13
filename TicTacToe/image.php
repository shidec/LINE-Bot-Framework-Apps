<?php
#read url segment
$link = $_SERVER['PHP_SELF'];
$link_array = explode('/',$link);
$size = $link_array[count($link_array) - 1];
$fields = $link_array[count($link_array) - 2];

#create image from selected file
$bg = imagecreatefrompng("samples/bg_ttt.png");
$img_o = imagecreatefrompng("samples/o.png");
$img_x = imagecreatefrompng("samples/x.png");

for($y = 0; $y < 3; $y++){
	for($x = 0; $x < 3; $x++){
		$idx = $y * 3 + $x;
		$value = substr($fields, $idx, 1);
		if($value == 1){
			imagecopy($bg, $img_o, $x * 200, $y * 200, 0, 0, 200, 200);
		}else if($value == 2){
			imagecopy($bg, $img_x, $x * 200, $y * 200, 0, 0, 200, 200);
		}
	
	}	
}

#create image for result
$result = imagecreate($size, $size);

// send the right headers
header("Content-Type: image/png");
#resize the image
imagecopyresized($result, $bg, 0, 0, 0, 0, $size, $size, 600, 600);
#dump the picture
imagepng($result);
#free the memory
imagedestroy($img);
imagedestroy($result);