<?php
//*************************************************************
define("_AUTOLOADUSER","autoloadone");
define("_AUTOLOADPASSWORD","autoloadone");
define("_AUTOLOADENTER",true); // if you want to autoload (no user or password) then set to true

//*************************************************************
$t1=microtime(true);
define("AUTOLOADONEVERSION","1.0");

$rooturl=__DIR__;
$fileGen=__DIR__."/autoinclude.php";
$savefile=0;
$stop=0;
$button=0;
$excludeNS="";
$excludePath="";
$log="";
$result="";
// @noautoload
/**
 * @noautoload
 */
if (php_sapi_name() == "cli") {
    // In cli-mode
    var_dump($argv);
    if (array_search("-current",$argv)!==false) {
        $rooturl=getcwd();
        $fileGen=getcwd()."/autoinclude.php";
        $savefile=1;
        $stop=0;
        $button=1;
        $excludeNS="";
        $excludePath="";
        echo "------------------------------------------------------------------\n";
        echo " AutoIncludeOne Generator ".AUTOLOADONEVERSION." (c) Jorge Castro\n";
        echo "------------------------------------------------------------------\n";
        echo "COMMAND.COM\n";
        echo "LOAD BIOS\n";
        echo "-folder ".$rooturl." (folder to scan)\n";
        echo "-filegen ".$fileGen." (file to generate)\n";
        echo "-save ".($savefile?"yes":"no")." (save filegen)\n";
        echo "-excludens ".$excludeNS." (namespace excluded)\n";
        echo "-excludepath ".$excludePath."\n";
        echo "------------------------------------------------------------------\n";
    }
} else {
    // Not in cli-mode
    @session_start();
    $logged=@$_SESSION["log"];
    if (!$logged) {
        $user=@$_POST["user"];
        $password=@$_POST["password"];
        if (($user==_AUTOLOADUSER && $password=_AUTOLOADPASSWORD) || _AUTOLOADENTER ) {
            $_SESSION["log"]="1";
            $logged=1;
        } else {
            sleep(1); // sleep a second
            $_SESSION["log"]="0";
            @session_destroy();
        }
        @session_write_close();
    } else {
        $rooturl=@$_POST["rooturl"]?$_POST["rooturl"]:$rooturl;
        $fileGen=@$_POST["fileGen"]?$_POST["fileGen"]:$fileGen;
        $savefile=@$_POST["savefile"];
        $stop=@$_POST["stop"];
        $button=@$_POST["button"];
        if ($button=="logout") {
            @session_destroy();
            $logged=0;
            @session_write_close();
        }


        //$rooturl='D:\Dropbox\www\currentproject\AutoLoadOne\test';
        //$rooturl='D:\Dropbox\www\currentproject\termo2';
        //$excludeNS="_AutoInclude"; // without trailing
        //$excludePath="vendor/pchart/class"; // without trailing
        //$fileGen="D:\Dropbox\www\currentproject\\termo2\\autoinclude.php";
    }



}

function genautoinclude($file,$namespaces,$namespacesAlt,$savefile) {
    if ($savefile) {
        $fp = fopen($file, "w");
    }
    $template=<<<'EOD'
<?php
/**
 * This class is used for autocomplete.
 * Class _AutoInclude
 * @noautoload
 * @generated by AutoLoadOne {{version}} generated {{date}}
 * @copyright Copyright Jorge Castro C - MIT License.
 */
class _AutoInclude
{
    var $debug=false;
    private $_arrautoincludeCustom = array(
{{custom}}
    );
    private $_arrautoinclude = array(
{{include}}
    );
    /**
     * _AutoInclude constructor.
     * @param bool $debug
     */
    public function __construct(bool $debug=false)
    {
        $this->debug = $debug;
    }
    /**
     * @param $class_name
     * @throws Exception
     */
    public function auto($class_name) {
        // its called only if the class is not loaded.
        $ns = dirname($class_name); // without trailing
        $ns=($ns==".")?"":$ns;        
        $cls = basename($class_name);
        // special cases
        if (isset($this->_arrautoincludeCustom[$class_name])) {
            $this->loadIfExists($this->_arrautoincludeCustom[$class_name] );
            return;
        }
        // normal (folder) cases
        if (isset($this->_arrautoinclude[$ns])) {
            $this->loadIfExists($this->_arrautoinclude[$ns] . "\\" . $cls . ".php");
            return;
        }
    }

    /**
     * @param $filename
     * @throws Exception
     */
    public function loadIfExists($filename)
    {
        if (@file_exists(__DIR__."\\".$filename)) {
            include __DIR__."\\".$filename;
        } else {
            if ($this->debug) {
                throw  new Exception("AutoLoadOne Error: Loading file [".__DIR__."\\".$filename."] for class [".basename($filename)."]");
            } else {
                throw  new Exception("AutoLoadOne Error: No file found.");
            }
        }
    }
} // end of the class _AutoInclude
if (defined('_AUTOLOADONEDEBUG')) {
    $_autoInclude=new _AutoInclude(_AUTOLOADONEDEBUG);
} else {
    $_autoInclude=new _AutoInclude(false);
}
spl_autoload_register(function ($class_name)
{
    global $_autoInclude;
    $_autoInclude->auto($class_name);
});
EOD;
    $custom="";
    foreach($namespacesAlt as $k=>$v) {
        $custom.="\t\t'$k' => '$v',\n";
    }
    if ($custom!="") {
        $custom=substr($custom,0,-2);
    }
    $include="";
    foreach($namespaces as $k=>$v) {
        $include.="\t\t'$k' => '$v',\n";
    }
    if ($include!="") {
        $include=substr($include,0,-2);
    }

    $template=str_replace("{{custom}}",$custom,$template);
    $template=str_replace("{{include}}",$include,$template);
    $template=str_replace("{{version}}",AUTOLOADONEVERSION,$template);
    $template=str_replace("{{date}}", date("Y/m/d h:i:s"),$template);

    if ($savefile) {
        fwrite($fp, $template);
        fclose($fp);
    }
    return $template;

}

function listFolderFiles($dir) {
    $arr=array();
    listFolderFilesAlt($dir,$arr);
    return $arr;
}
function listFolderFilesAlt($dir,&$list){
    $ffs = scandir($dir);
    foreach ( $ffs as $ff ){
        if ( $ff != '.' && $ff != '..' ){
            if ( strlen($ff)>=5 ) {
                if ( substr($ff, -4) == '.php' ) {
                    $list[] = $dir.'/'.$ff;
                }
            }
            if( is_dir($dir.'/'.$ff) )
                listFolderFilesAlt($dir.'/'.$ff,$list);
        }
    }
    return $list;
}




/**
 * @param $filename
 * @return array
 */
function parsePHPFile($filename) {
    $r=array();
    $content=file_get_contents($filename);
    try {
        $tokens = token_get_all($content, TOKEN_PARSE);
        /*
        echo $filename;
        echo "<pre>";
        var_dump(token_name(377));
        var_dump(token_name(378));
        var_dump($tokens);
        echo "</pre>";
        die(1);
        */
    } catch(Exception $ex) {
        echo "error in $filename\n";
        die(1);
    }
    foreach($tokens as $p=>$token) {
        if (is_array($token) && ($token[0]==T_COMMENT ||$token[0]==T_DOC_COMMENT)) {
            if (strpos($token[1],"@noautoload")!==false) {
                return array();
            }
        }
    }
    $nameSpace="";
    $className="";
    foreach($tokens as $p=>$token) {
        if (is_array($token) && $token[0]==T_NAMESPACE) {
            // encontramos un namespace
            $ns="";
            for($i=$p+2;$i<$p+30;$i++) {
                if (is_array($tokens[$i])) {
                    $ns.=$tokens[$i][1];
                } else {
                    // tokens[$p]==';' ??
                    break;
                }
            }
            $nameSpace=$ns;
        }
        if (is_array($token) && $token[0]==T_CLASS) {
            // encontramos una clase
            for($i=$p+2;$i<$p+30;$i++) {
                if (is_array($tokens[$i]) && $tokens[$i][0]==T_STRING) {
                    $className=$tokens[$i][1];
                    break;
                }
            }
            $r[]=array('namespace'=>trim($nameSpace),'classname'=>trim($className));
        }

    } // foreach
    return $r;
}

function genPath($path) {
    $path=str_replace("\\","/",$path);
    //$path.="/test/folder";
    global $baseGen;
    if (strpos($path,$baseGen)==0) {
        $min1=strripos($path,"/");
        $min2=strripos($baseGen,"/");
        //$min=min(strlen($path),strlen($baseGen));
        $min=min($min1,$min2);
        $baseCommon=$min;
        for($i=0;$i<$min;$i++) {
            if (substr($path,0,$i)!=substr($baseGen,0,$i)) {
                $baseCommon=$i-2;

                break;
            }
        }
        //$sub=str_replace($path,"",$baseGen);

        // cuanto hay que retroceder

        $c=substr_count(substr($baseGen,$baseCommon),"/");
        $r=str_repeat("/..",$c);
        // hay que avanzar
        $r2=substr($path,$baseCommon);
        /*
        echo "common:".substr($baseGen,0,$baseCommon)."<br>";
        echo $path."<br>";
        echo $baseGen."<br>";
        echo $r.$r2."<br>";
        */
        return $r.$r2;
        //die(1);
    } else {
        /*
        echo $path."<br>";
        echo $baseGen."<br>";
        die(1);
        */
        $r=substr($path, strlen($baseGen));
    }
    return $r;
}

/**
 * returns dir name linux way
 * @param $fullUrl
 * @return mixed|string
 */
function dirNameLinux($fullUrl) {
    $dir = dirname($fullUrl);
    $dir=str_replace("\\","/",$dir);
    return $dir;
}

function addLog($txt) {
    global $log;
    if (php_sapi_name() == "cli") {
        echo $txt . "\n";
    } else {
        $log .= $txt . "\n";
    }
}

if ($rooturl) {
    $baseGen=dirNameLinux($fileGen);
    $files = listFolderFiles($rooturl);


    $ns = array();
    $nsAlt = array();

    $excludeNSArr = str_replace("\n", "", $excludeNS);
    $excludeNSArr = str_replace("\r", "", $excludeNSArr);
    $excludeNSArr = str_replace(" ", "", $excludeNSArr);
    $excludeNSArr = explode(",", $excludeNSArr);

    $excludePathArr = str_replace("\n", "", $excludePath);
    $excludePathArr = str_replace("\r", "", $excludePathArr);
    $excludePathArr = str_replace(" ", "", $excludePathArr);
    $excludePathArr = explode(",", $excludePathArr);

    $log = "";
    $result = "";
    $num = 0;
    if ($button) {
        foreach ($files as $f) {
            $pArr = parsePHPFile($f);
            $dir = dirNameLinux($f);
            $dir = genPath($dir);
            $full = genPath($f);
            $urlFull = dirNameLinux($full);
            $basefile = basename($f);
            //var_dump($f);
            //var_dump($dir);
            //die(1);

            foreach ($pArr as $p) {


                $nsp = $p['namespace'];
                $cs = $p['classname'];

                $altUrl = ($nsp != "") ? $nsp . '\\' . $cs : $cs;

                if ($nsp != "" || $cs != "") {
                    if ((!isset($ns[$nsp]) || $ns[$nsp] == $dir) && $basefile == $cs . ".php") {
                        // namespace doesn't exist and the class is equals to the name
                        // adding as a folder

                        if ((!in_array($nsp, $excludeNSArr) || $nsp=="") && !in_array($dir, $excludePathArr)) {
                            if ($nsp=="") {
                                addLog("Adding Full (empty namespace): $altUrl=$full");
                                $nsAlt[$altUrl] = $full;
                            } else {
                                $ns[$nsp] = $dir;
                                addLog("Adding Folder: $nsp=$dir");
                            }

                        }
                    } else {
                        // custom namespace 1-1
                        // a) if filename has different name with the class
                        // b) if namespace is already defined for a different folder.
                        // c) multiple namespaces
                        if (isset($nsAlt[$altUrl])) {
                            addLog("Error Conflict:Class on $altUrl already defined.");
                            if ($stop) {
                                die(1);
                            }
                        } else {
                            if ((!in_array($altUrl, $excludeNSArr) || $nsp=="") && !in_array($urlFull, $excludePathArr)) {
                                addLog("Adding Full: $altUrl=$full");
                                $nsAlt[$altUrl] = $full;
                            }
                        }
                    }
                }
                $fShort = substr($f, strlen($rooturl));
            }
            if (count($pArr)==0) {
                addLog("Ignoring $full");
            }
        }
        $result = genautoinclude($fileGen, $ns, $nsAlt, $savefile);
    }


}





if (php_sapi_name() == "cli") {
    $t2=microtime(true);
    echo "\n".(round(($t2-$t1)*1000)/1000)."ms. Finished\n";


} else {

if (!$logged) {
    $web=<<<'LOGS'
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous" />

  </head>
  
  <body>
  <br>
    <div class="section">
      <div class="container">
        <div class="row">
          <div class="col-md-12">
            <div class="panel panel-primary">
              <div class="panel-heading">
                <h3 class="panel-title">Login Screen</h3>
              </div>
              <div class="panel-body">
                <form class="form-horizontal" role="form" method="post">
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label for="inputEmail3" class="control-label">User</label>
                    </div>
                    <div class="col-sm-10">
                      <input type="text" name="user" class="form-control" id="inputEmail3" placeholder="User">
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label for="inputPassword3" class="control-label">Password</label>
                    </div>
                    <div class="col-sm-10">
                      <input type="password" name="password" class="form-control" id="inputPassword3" placeholder="Password">
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                      <button type="submit" class="btn btn-default">Sign in</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>

</html>
LOGS;
    echo $web;
}   else {



    $web = <<<'TEM1'
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous" />    
</head>
      
  <body>
  <br>
    <div class="section">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
            <div class="panel panel-primary">
              <div class="panel-heading">
                <h3 class="panel-title"><a href="https://github.com/EFTEC/AutoLoadOne">AutoIncludeOne</a> Generator {{version}}.</h3>
              </div>             
              <div class="panel-body">
                <form class="form-horizontal" role="form" method="post">
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label class="control-label">Root Folder</label>
                    </div>
                    <div class="col-sm-10">
                      <input type="text" class="form-control" placeholder="ex. \htdoc\web  or c:\htdoc\web"
                      name="rooturl" value="{{rooturl}}">
                      <em>Root folder to scan.</em>
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label class="control-label">Generated File
                        <br>
                      </label>
                    </div>
                    <div class="col-sm-10">
                      <input type="text" class="form-control" placeholder="ex. /etc/httpd/web/autoinclude.php or c:\apache\htdoc\autoinclude.php"
                      name="fileGen" value="{{fileGen}}">
                      <em>Full path (local file) of the autoinclude file that will be generated.<br>
                      Note: This path is also used to determine the relativity of the includes</em>
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                      <div class="checkbox">
                        <label>
                          <input type="checkbox" name="savefile" value="1" {{savefile}}>Save File</label>
                      </div>
                    </div>
                  </div>                  
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label class="control-label">Excluded Namespace
                        <br>
                      </label>
                    </div>
                    <div class="col-sm-10">
                      <textarea class="form-control" name="excludeNS">{{excludeNS}}</textarea>
                      <em>Namespaces without trailing "/" separated by comma. Example
                      /mynamespace</em></div>
                  </div>
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label class="control-label">Excluded Path</label>
                    </div>
                    <div class="col-sm-10">
                      <textarea class="form-control" name="excludePath">{{excludePath}}</textarea>
                      <em>Relative path without trailing "/" separated by comma. Example
                      vendor/pchart/class</em></div>
                  </div>

                  <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                      <div class="checkbox">
                        <label>
                          <input type="checkbox" name="stop" value=1 {{stop}}>
                          <em>Stop on conflict (class defined more than one time)</em></label>
                      </div>
                    </div>
                  </div>
                  <div class="form-group" draggable="true">
                    <div class="col-sm-2">
                      <label class="control-label">Log</label>
                    </div>
                    <div class="col-sm-10">
                      <textarea class="form-control" readonly rows="10">{{log}}</textarea>
                    </div>
                  </div>                  
                  <div class="form-group" draggable="true">
                    <div class="col-sm-2">
                      <label class="control-label">Result</label>
                    </div>
                    <div class="col-sm-10">
                      <textarea class="form-control" readonly rows="10">{{result}}</textarea>
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                      <button type="submit" name="button" value="1" class="btn btn-default">Generate</button>
                      &nbsp;&nbsp;&nbsp;
                      <button type="submit" name="button" value="logout" class="btn btn-default">Logout</button>
                    </div>
                  </div>
                </form>
              </div>
              <div class="panel-footer">
                <h3 class="panel-title">&copy; <a href="https://github.com/EFTEC/AutoLoadOne">Jorge Castro C.</a> {{ms}}</h3>
              </div> 
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>

</html>    
TEM1;


    $web=str_replace("{{rooturl}}",$rooturl,$web);
    $web=str_replace("{{fileGen}}",$fileGen,$web);



    $web=str_replace("{{excludeNS}}",$excludeNS,$web);
    $web=str_replace("{{excludePath}}",$excludePath,$web);
    $web=str_replace("{{savefile}}",($savefile)?"checked":"",$web);
    $web=str_replace("{{stop}}",($stop)?"checked":"",$web);

    $web=str_replace("{{log}}",$log,$web);
    $web=str_replace("{{version}}",AUTOLOADONEVERSION,$web);
    $web=str_replace("{{result}}",$result,$web);

    $t2=microtime(true);
    $ms=(round(($t2-$t1)*1000)/1000)."ms.";

    $web=str_replace("{{ms}}",$ms,$web);
    echo $web;
}
}

