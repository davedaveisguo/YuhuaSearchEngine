<?php
include("config.php");
include("classes/DomDocumentParser.php");

$alredyCrawled=array();
$crawling=array();
$alreadyFoundImages=array();


// check duplicated url
function linkExists($url)
{
    global $con;

    $query=$con->prepare("SELECT * FROM sites WHERE url=:url");

    $query->bindParam(":url", $url);
    $query->execute();

    return $query->rowCount() !=0;
}

// insert a tags
function insertLink($url, $title, $description,$keywords)
{
    global $con;

    $query=$con->prepare("INSERT INTO sites(url, title, description, keywords) 
    VALUES (:url, :title, :description, :keywords)");

    $query->bindParam(":url", $url);
    $query->bindParam(":title", $title);
    $query->bindParam(":description", $description);
    $query->bindParam(":keywords", $keywords);

    return $query->execute();
}

// insert image
function insertImage($url, $src, $alt, $title)
{
    global $con;

    $query=$con->prepare("INSERT INTO images(siteUrl, imageUrl, alt, title) 
    VALUES (:siteUrl, :imageUrl, :alt, :title)");

    $query->bindParam(":siteUrl", $url);
    $query->bindParam(":imageUrl", $src);
    $query->bindParam(":alt", $alt);
    $query->bindParam(":title", $title);

    $query->execute();
}




// format src
function createLink($src, $url)
{
    $scheme=parse_url($url)["scheme"];  // http, https
    $host=parse_url($url)["host"];

    if(substr($src,0,2)=="//")
    {
        $src=$scheme . ":" . $src;
    }else if(substr($src,0,1)=="/")
    {
        $src=$scheme . "://" . $host.$src;
    }else if (substr($src,0,2)=="./")
    {
        $src=$scheme . "://" . $host. dirname(parse_url($url)["path"]).substr($src,1);
    }else if (substr($src,0,3)=="../")
    {
        $src=$scheme . "://" . $host. "/". $src;
    }else if (substr($src,0,5)!="https" && substr($src,0,4)!="http")
    {
        $src=$scheme . "://" . $host. "/". $src;
    }

    return $src;
}






// get meta details and insert into sites and images
function getDetails($url)
{

    return;
    global $alreadyFoundImages;
    $parser=new DomDocumentParser($url);

    $titleArray= $parser->getTitletags();

    if(sizeof($titleArray)==0 || $titleArray->item(0)==NULL)
    {
        return;
    }

    $title=$titleArray->item(0)->nodeValue;

    $title=str_replace("\n","",$title);

    if($title=="")
    {
        return;
    }

    $description="";
    $keywords="";

    $metaArray=$parser->getMetatags();

    foreach ($metaArray as $meta) {
        if($meta->getAttribute("name")=="description")
        {
            $description=$meta->getAttribute("content");
        }

        if($meta->getAttribute("name")=="keywords")
        {
            $keywords=$meta->getAttribute("content");
        }
    }
    $description=str_replace("\n","",$description);
    $keywords=str_replace("\n","",$keywords);

    if(linkExists($url)){
        echo "$url already exists<br>";
    }else if(insertLink($url,$title,$description,$keywords))
    {
        echo "SUCCESS: $url <br>";
    }else
    {
        echo "ERROR: Failed to insert $url <br>";
    }


    $imageArray=$parser->getImages();
    foreach ($imageArray as $image) {
        $src=$image->getAttribute("src");
        $alt=$image->getAttribute("alt");
        $title=$image->getAttribute("title");
        
        if(!$title && !$alt)
        {
            continue;
        }
        // formate src
        $src=createLink($src, $url);

        if(!in_array($src, $alreadyFoundImages))
        {
            // insert into array
            $alreadyFoundImages[]=$src;

            //Insert the image
            insertImage($url,$src,$alt,$title);

        }

        else return;
    } 


}


function followLinks($url)
{
    global $alredyCrawled;
    global $crawling;
    $parser=new DomDocumentParser($url);

    $linkList= $parser->getLinks();


    foreach($linkList as $link)
    {
        $href=$link->getAttribute("href");

        if(strpos($href, "#")!==false)
        {
            continue;
        }else if(substr($href,0,11)=="javascript:")
        {
            continue;
        }

        $href=createLink($href,$url);

        if(!in_array($href, $alredyCrawled))
        {
            $alredyCrawled[] = $href;
            $crawling[]=$href;

            getDetails($href);
            //Insert $href
        }


    }
    // remove the top element
    array_shift($crawling);

    foreach($crawling as $site)
    {
        followLinks($site);
    }
}

//$startUrl="http://www.bbc.com";

followLinks($startUrl);

?>