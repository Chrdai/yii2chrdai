<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\widgets\JsBlock;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel yii\data\ActiveDataProvider */
/* @var $map yii\data\ActiveDataProvider */

$this->title = '个人信息';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('/css/standard.css');
?>
<table>
    <tr>
        <td>
            <div>
                <div class="headimg">
                    <img src="<?= empty($userInfo['photo']) ? '/img/default.png' :$userInfo['photo']; ?>" id="picture">
                    <input type="hidden" id="photoUrl" value="<?=$userInfo['photo'];?>" status="1" data=""/>
                </div>
                <div class="minFilenames"><ul></ul></div>

                <div class="minFilebox">
                    <input type="file" name="fileList" multiple id="electPic"/>修改头像
                </div>

                <div>请上传小于5M的JPG、JPEG、PNG、GIF图片文件</div>
                <div class="red" id="percent"></div>
            </div>
        </td>
    </tr>
</table>

<?php $this->registerJsFile("/js/jquery.upfile.js", [
    'depends' => 'yii\web\JqueryAsset'
]) ?>

<?php JsBlock::begin() ?>
<script>
    $('#electPic').uploadFile({
        'eventType' : ['change','#electPic'],
        'url' : '<?= Url::to(['/standard/profile/upload']) ?>',
        'fileType' : 'jpg|png|sql|doc|bz2|php',
        'fileSize' : 1000,
        'size' : "",
        'fileName' : "fileList",
        onSelect: function(selectFiles){
            var picPath = selectFiles[0].path;
            $("#picture").attr("src",picPath);
            $("#photoUrl").attr("data",picPath);
            var _html = '';
            filesIndex = selectFiles.length;
            for(var i=0; i<selectFiles.length; i++){
                _html += '<li id="'+selectFiles[i].id+'">' + selectFiles[i].name + '<span onclick="delFile($(this))" data-name="'+selectFiles[i].name+'" data-id="' + i + '" class="del" title="删除">×</span><div class="min_progress"><div class="bar">0%</div></div></li>';
            }
            $(".minFilenames>ul").append(_html);
        },
        onSelectFail: function (errText) {
            $('#tips').text(errText);
        },
        onProgress: function(total, loaded, files){
            var per = Math.floor(100*loaded/total) + '%';  //已经上传的百分比
//            $(".minFilenames>ul").html(per);
            $("#" + files.id).find(".bar").html(per);//.css("width", per +"%");
        },
        onSuccess: function(responce){
            console.log(responce);
        }

    })

</script>
<?php JsBlock::end() ?>
