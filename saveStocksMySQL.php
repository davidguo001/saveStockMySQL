<?php
    if(isset($_POST['action'])){
        // fileNames[sym] = {fileName: [filePath, tableNames]}
        // fileName = 'rawData' | 'testData' | 'charts' | 'analyzeChart'
        // $dataObj[$sym][$name][0] = $filePath
        // $dataObj[$sym][$name][1][$tableName] = data
        header("content-type:application/json");
        
        $fileNames = $_POST['fileNames'];
        $status = "";
        $info = "";
        $tableNames = null;
        
        foreach($fileNames as $sym => $fileNameObj){
            // fileNameObj = [filePath, tableNames]
            foreach($fileNameObj as $name => $fileArray){
                // fileArray = [filePath, tableNames]
                
                $filePathArray = explode("/", $fileArray[0]);
                array_pop($filePathArray);
                $filePathStr = implode("/", $filePathArray);
                
                if(!file_exists($filePathStr)){
                    mkdir($filePathStr, 0777, true);
                }
                
                $fileName = $fileArray[0];
                $db = new SQLite3($fileName);
                $tableNames = $fileArray[1];
                $tableName = "";
                
                foreach($tableNames as $tableName => $dataArray){
                    $tableArray = [];
                    
                    $query = $db->query("SELECT name FROM sqlite_master where type = 'table' AND name = '".$tableName."'");
                    while($table = $query->fetchArray()){
                        array_push($tableArray, $table['name']);
                    }
                    
                    if(count($tableArray) == 0){
                        // No such table is created
                        $doQuery = true;
                        
                        if($name ==  "source"){
                            $query = "CREATE TABLE '".$tableName."' ('timestamp' INTEGER PRIMARY KEY UNIQUE, 'high' REAL, 'low' REAL, 'open' REAL, 'close' REAL)";
                        }else if($name ==  "testData"){
                            $query = "CREATE TABLE '".$tableName."' ('timestamp' INTEGER PRIMARY KEY UNIQUE, 'high' REAL, 'low' REAL, 'open' REAL, 'close' REAL)";
                        }else if($name ==  "charts"){
                            if($tableName == "chartData"){
                                $query = "CREATE TABLE '".$tableName."' ('timeStr' TEXT PRIMARY KEY UNIQUE, 'high' REAL, 'low' REAL, 'open' REAL, 'close' REAL)";
                            }else{
                                $query = "CREATE TABLE '".$tableName."' ('index' INTEGER PRIMARY KEY UNIQUE, 'chartIndex' INTEGER, 'chartValue' REAL)";
                            }
                        }else if($name ==  "analyzeChart"){
                            $query = "CREATE TABLE '".$tableName."' ('index' INTEGER PRIMARY KEY UNIQUE, 'startIndex' INTEGER, 'h1' INTEGER, 'h2' INTEGER, 'l1' INTEGER, 'l2' INTEGER, 'type' INTEGER)";
                        }else{
                            $doQuery = false;
                        }
                        
                        if($doQuery){
                            $exec_respond = $db->exec($query);
                            
                            if($exec_respond){
                                $valueArr = [];
                                
                                for($i=0, $len=count($dataArray); $i<$len; $i++){
                                    $tempValue = implode(", ", $dataArray[$i]);
                                    $tempValue = "(".$tempValue.")";
                                    array_push($valueArr, $tempValue);
                                }
                                $value = implode(", ", $valueArr);
                                
                                if($name ==  "source"){
                                    $query = "INSERT INTO 'main'.'".$tableName."' ('timestamp', 'high', 'low', 'open', 'close') VALUES ".$value;
                                }else if($name ==  "testData"){
                                    $query = "INSERT INTO 'main'.'".$tableName."' ('timestamp', 'high', 'low', 'open', 'close') VALUES ".$value;
                                }else if($name ==  "charts"){
                                    if($tableName == "chartData"){
                                        $query = "INSERT INTO 'main'.'".$tableName."' ('timeStr', 'high', 'low', 'open', 'close') VALUES ".$value;
                                    }else{
                                        $query = "INSERT INTO 'main'.'".$tableName."' ('chartIndex', 'chartValue') VALUES ".$value;
                                    }
                                }else if($name ==  "analyzeChart"){
                                    $query = "INSERT INTO 'main'.'".$tableName."' ('startIndex', 'h1', 'h2', 'l1', 'l2', 'type') VALUES ".$value;
                                }else{
                                    $doQuery = false;
                                }
                                
                                echo $query;
                                
                                if($doQuery){
                                    $exec_respond = $db->exec($query);
                                    
                                    if($exec_respond){
                                        $status = "done";
                                        $info .= "Added entries. ".$sym." - ".$fileName.", ".$tableName."\n";
                                    }else{
                                        $status = "error";
                                        $info .= "Can't insert data. ".$sym." - ".$fileName.", ".$tableName."\n";
                                    }
                                }
                                
                            }else{
                                $status = "error";
                                $info .= "Can't create the table. ".$sym." - ".$fileName.", ".$tableName."\n";
                            }
                        }
                    }elseif(count($tableArray) == 1){
                        if($name == "charts" && $tableName != "chartData"){
                            // Overwrite the existing table, if tables are for those anchor points in charts
                            $query = "DROP TABLE '".$tableName."'";
                            $exec_respond = $db->exec($query);
                            
                            if($exec_respond){
                                $query = "CREATE TABLE '".$tableName."' ('index' INTEGER PRIMARY KEY UNIQUE, 'chartIndex' INTEGER, 'chartValue' REAL)";
                                $exec_respond = $db->exec($query);
                                
                                if($exec_respond){
                                    $valueArr = [];
                                    
                                    for($i=0, $len=count($dataArray); $i<$len; $i++){
                                        $tempValue = implode(", ", $dataArray[$i]);
                                        $tempValue = "(".$tempValue.")";
                                        array_push($valueArr, $tempValue);
                                    }
                                    $value = implode(", ", $valueArr);
                                    
                                    $query = "INSERT INTO 'main'.'".$tableName."' ('chartIndex', 'chartValue') VALUES ".$value;
                                    $exec_respond = $db->exec($query);
                                    
                                    if($exec_respond){
                                        $status = "done";
                                        $info .= "Added entries. ".$sym." - ".$fileName.", ".$tableName."\n";
                                    }else{
                                        $status = "error";
                                        $info .= "Can't insert data. ".$sym." - ".$fileName.", ".$tableName."\n";
                                    }
                                }else{
                                    $status = "error";
                                    $info .= "Can't create the table. ".$sym." - ".$fileName.", ".$tableName."\n";
                                }
                            }else{
                                $status = "error";
                                $info .= "Can't drop existing table. ".$sym." - ".$fileName.", ".$tableName."\n";
                            }
                        }else{
                            $doQuery = true;
                            
                            $valueArr = [];
                            
                            for($i=0, $len=count($dataArray); $i<$len; $i++){
                                $tempValue = implode(", ", $dataArray[$i]);
                                $tempValue = "(".$tempValue.")";
                                array_push($valueArr, $tempValue);
                            }
                            
                            $value = implode(", ", $valueArr);
                            
                            if($name == "source" || $name == "testData"){
                                $query = "INSERT INTO '".$tableName."' ('timestamp', 'high', 'low', 'open', 'close') VALUES ".$value;
                            }else if($name ==  "charts" && $tableName == "chartData"){
                                $query = "INSERT INTO 'main'.'".$tableName."' ('timeStr', 'high', 'low', 'open', 'close') VALUES ".$value;
                            }else if($name ==  "analyzeChart"){
                                $query = "INSERT INTO 'main'.'".$tableName."' ('startIndex', 'h1', 'h2', 'l1', 'l2', 'type') VALUES ".$value;
                            }else{
                                $doQuery = false;
                            }
                            
                            if($doQuery){
                                $exec_respond = $db->exec($query);
                                
                                if($exec_respond){
                                    $status = "done";
                                    $info .= "Added entries. ".$sym." - ".$fileName.", ".$tableName."\n";
                                }else{
                                    $status = "error";
                                    $info .= "Can't insert data. ".$sym." - ".$fileName.", ".$tableName."\n";
                                }
                            }
                        }
                    }
                }
            }
        }
        
        echo json_encode(array("status" => $status, "info" => $info));
        
    }else{
        echo "saveStock";
    }
?>