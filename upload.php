<?php
$base_url = 'http://localhost/simple/';
$target_dir = "uploads/";
$result_dir = "results/";

$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$fileType = pathinfo($target_file,PATHINFO_EXTENSION);

if(isset($_POST["submit"])) {
    if (file_exists($target_file))
        unlink($target_file);
    $files = glob('results/*'); // get all file names
    foreach($files as $file){ // iterate files
      if(is_file($file))
        unlink($file); // delete file
    }

    if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $target_file))
    {
        slecho ("Uploaded successfully. The file saved in " . $target_file . "\n");
        slecho ("Convert ". $target_file ." - Initializing...\n");

        slecho ("Starting first step...\n");
        $fileInput = $target_file;
        $fileOutput = $result_dir . "F00";
        $fileKeys = $result_dir . "R";
        doStepAction($fileInput, $fileOutput, $fileKeys);
        slecho ("Generated F00 and R.\n");
        ///////////////
        slecho ("Starting second step...\n");

        $fileInput = $result_dir . "R";
        $fileOutput = $result_dir . "R00";
        $fileKeys = $result_dir . "K";
        doStepAction($fileInput, $fileOutput, $fileKeys);

        slecho ("Generated R00 and K.\n");

        ///////////////
        slecho ("Starting third step...\n");

        $fileInput = $result_dir . "K";
        $fileOutput = $result_dir . "K00";
        $fileKeys = $result_dir . "L";
        doStepAction($fileInput, $fileOutput, $fileKeys);

        slecho ("Generated K00 and L.\n");

        slecho ("Finished. Results are in <results> directory on the server.");

        // Create zip
        slecho ("Creating result zip file.");
        $files_to_zip = array(
            $result_dir . 'F00',
            $result_dir . 'K',
            $result_dir . 'K00',
            $result_dir . 'L',
            $result_dir . 'R',
            $result_dir . 'R00'
        );
        //if true, good; if false, zip creation failed
        $result = create_zip($files_to_zip,'result.zip', true);

        slecho ("Zip file created on the server.");
        slecho ("<a href='". $base_url ."result.zip'>To download result, click this.</a>");

    } else{
        slecho ("There was an error uploading the file, please try again!\n");
    }

}

///////////////////////////////////////////////////////////////////////////////////////////
function slecho($str) {
    echo "$str"."<br/>";
}

function doStepAction($fileInput, $fileOutput, $fileKeys) {
    $handle = fopen($fileInput, "rb"); 
    $fsize = filesize($fileInput); 
    $contents = fread($handle, $fsize); 
    $byte_data_file = unpack("C*",$contents); 

    $byte_arr_key = generateKeyData($fsize);

    $byte_arr_output = doConvert($byte_data_file, $byte_arr_key);

    saveVectorDataToFile($byte_arr_key, $fileKeys);
    saveVectorDataToFile($byte_arr_output, $fileOutput);

    return true;
}

function generateKeyData($totalBytes) {
    $vecKeys = array();

    $curSum = 0;

    while ($curSum < $totalBytes)
    {
        $key = getByteRand();
        $curSum += $key;

        array_push($vecKeys, $key);
    }

    $offset = $curSum - $totalBytes;
    $vecKeys[count($vecKeys) - 1] -= $offset;

    return $vecKeys;
}

function getByteRand() {
    $val = rand(1,255);
    $ret = unpack("C*", pack("L", $val));
    return $ret[1];
}

function doConvert($pInputData, $vecKeyData) {
    $vecOutputData = array();

    $curPos = 0;
    for ($i = 0; $i < count($vecKeyData); $i ++) {
        $key = $vecKeyData[$i];

        $part = array_slice($pInputData, $curPos, $key);
        $curPos += $key;
        $vecOutputData = array_merge($vecOutputData, $part);
        array_push($vecOutputData, 0x00);
    }

    return $vecOutputData;
}

function saveVectorDataToFile($vecData, $strFilePath) {
    $handle = fopen($strFilePath, "wb"); 
    foreach($vecData as $d) {
        fwrite($handle, pack('C', $d));
    }
    fclose($handle);

    return true;
}

/// zip file
function create_zip($files = array(),$destination = '',$overwrite = false) {
	//if the zip file already exists and overwrite is false, return false
	if(file_exists($destination) && !$overwrite) { return false; }
	//vars
	$valid_files = array();
	//if files were passed in...
	if(is_array($files)) {
		//cycle through each file
		foreach($files as $file) {
			//make sure the file exists
			if(file_exists($file)) {
				$valid_files[] = $file;
			}
		}
	}
	//if we have good files...
	if(count($valid_files)) {
		//create the archive
		$zip = new ZipArchive();
		if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
			return false;
		}
		//add the files
		foreach($valid_files as $file) {
			$zip->addFile($file,$file);
		}
		//debug
		//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
		
		//close the zip -- done!
		$zip->close();
		
		//check to make sure the file exists
		return file_exists($destination);
	}
	else
	{
		return false;
	}
}
?>
