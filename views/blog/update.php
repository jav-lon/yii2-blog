<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model jav_lon\blog\models\Blog */
/* @var $dataSelect \jav_lon\blog\models\Tag */

$this->title = "Update Blog: {$model->title}";
$this->params['breadcrumbs'][] = ['label' => 'Blogs', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="blog-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'dataSelect' => $dataSelect,
    ]) ?>

</div>
