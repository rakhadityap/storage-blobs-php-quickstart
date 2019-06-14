<?php
require_once './vendor/autoload.php';

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;

$connectionString = "DefaultEndpointsProtocol=https;AccountName=" . getenv('ACCOUNT_NAME') . ";AccountKey=" . getenv('ACCOUNT_KEY');

$blobClient = BlobRestProxy::createBlobService($connectionString);
$containerName = "macderakhacontainer";

if (!empty($_POST["submit"])) {

    $filename = $_FILES["fileToUpload"]["name"];
    $uploaded_file = $_FILES["fileToUpload"]["tmp_name"];

    $containers = $blobClient->listContainers();
    $containerExist = false;
    foreach ($containers->getContainers() as $container) {
        if ($containerName == $container->getName()) {
            $containerExist = true;
            break;
        }
    }

    try {
        if (!$containerExist) {
            $createContainerOptions = new CreateContainerOptions();
            $createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);

            $createContainerOptions->addMetaData("key1", "value1");
            $createContainerOptions->addMetaData("key2", "value2");

            $blobClient->createContainer($containerName, $createContainerOptions);
        }

        $myfile = file_get_contents($uploaded_file);

        $content = fopen($uploaded_file, "r");

        $uploadRes = $blobClient->createBlockBlob($containerName, $filename, $content);

        $uploadRes->getLastModified();
        $result = "Upload File Succeed";
    } catch (ServiceException $e) {
        $code = $e->getCode();
        $error_message = $e->getMessage();
        $result = $error_message;
    } catch (InvalidArgumentTypeException $e) {
        $code = $e->getCode();
        $error_message = $e->getMessage();
        $result = $error_message;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="style.css">
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/semantic-ui@2.4.2/dist/semantic.min.css">
    <script src="https://cdn.jsdelivr.net/npm/semantic-ui@2.4.2/dist/semantic.min.js"></script> -->
</head>

<body>
    <h1>Analisa Gambar dengan Azure Computer Vision</h1>
    <form id="form" action="" method="post" enctype="multipart/form-data">
        Select image to upload:<br>
        <input type="file" name="fileToUpload" id="fileToUpload">
        <input type="submit" value="Submit" name="submit" id="submit">
    </form><br />
    <table border="1">
        <tr>
            <th>No.</th>
            <th>File Name</th>
            <th>Image</th>
            <th>Action</th>
        </tr>
        <?php
        $listBlobsOptions = new ListBlobsOptions();
        $blobs = array();
        do {
            $number = 1;
            $result = $blobClient->listBlobs($containerName, $listBlobsOptions);
            foreach ($result->getBlobs() as $blob) {
                echo "<tr>
                    <td>$number</td>
                    <td>{$blob->getName()}</td>
                    <td><img id=\"img$number\" src=\"{$blob->getUrl()}\" height=\"100\" width=\"100\" data-url$number=\"{$blob->getUrl()}\"></td>
                    <td><button id=\"btn\" onclick=\"analyse($number)\">Analyse</button></td>
                </tr>";
                $number++;
            }

            $listBlobsOptions->setContinuationToken($result->getContinuationToken());
        } while ($result->getContinuationToken());
        ?>
    </table>
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <table>
                <tr>
                    <td>
                        <textarea id="responseTextArea" style="width:580px; height:400px;"></textarea>
                    </td>
                    <td>
                        <img id="result" style="max-width: 400px;">
                        <h3 id="captions"></h3>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
<script type="text/javascript">
    function analyse(position) {
        let url = $(`#img${position}`).data(`url${position}`);
        let modal = document.getElementById("modal");
        var btn = document.getElementById("btn");
        const subscriptionKey = "907f62a4eec5451184266255c427006c";
        const uriBase =
            "https://southeastasia.api.cognitive.microsoft.com/vision/v2.0/analyze";
        const params = {
            "visualFeatures": "Categories,Description,Color",
            "details": "",
            "language": "en",
        };

        console.log(url);
        $.ajax({
                url: uriBase + "?" + $.param(params),
                beforeSend: function(xhrObj) {
                    xhrObj.setRequestHeader("Content-Type", "application/json");
                    xhrObj.setRequestHeader(
                        "Ocp-Apim-Subscription-Key", subscriptionKey);
                },
                type: "POST",
                data: '{"url": ' + '"' + url + '"}',
            })
            .done(function(data) {
                let caption = data.description.captions[0].text;
                $('#responseTextArea').empty();
                $('#captions').empty();
                $("#responseTextArea").val(JSON.stringify(data, null, 2));
                $('#result').attr("src", url);
                $('#captions').append(caption)
                $('#modal').show();
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                let errorString = (errorThrown === "") ? "Error. " :
                    errorThrown + " (" + jqXHR.status + "): ";
                errorString += (jqXHR.responseText === "") ? "" :
                    jQuery.parseJSON(jqXHR.responseText).message;
                alert(errorString);
            });

    }
    $(document).ready((e) => {
        let span = document.getElementsByClassName("close")[0];
        let modal = document.getElementById("modal");
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    });
</script>

</html>