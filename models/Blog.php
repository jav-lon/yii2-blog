<?php

namespace jav_lon\blog\models;

use common\models\ImageManager;
use common\models\User;
use Yii;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\Url;
use yii\image\drivers\Image;
use yii\web\UploadedFile;

/**
 * This is the model class for table "blog".
 *
 * @property string $id
 * @property string $title
 * @property string $text
 * @property string $url
 * @property int $status_id
 * @property int $sort
 * @property int $image
 * @property int $file
 */
class Blog extends \yii\db\ActiveRecord
{
    public $_tags;
    public $file;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'blog';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'url'], 'required'],
            [['text'], 'string'],
            [['url'], 'unique'],
            [['status_id', 'sort'], 'integer'],
            [['sort'], 'integer', 'max' => 99, 'min' => 1],
            [['title', 'url'], 'string', 'max' => 150],
            [['image'], 'string', 'max' => 100],
            ['file', 'image'],
            ['_tags', 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Nomi',
            'text' => 'Matn',
            'url' => 'Url',
            'status_id' => 'Holati',
            'sort' => 'Sort',
            '_tags' => 'Teglar',
            'tagsAsString' => 'Teglar',
            'author.username' => 'Muallif Ismi',
            'updated_at' => 'Yangilandi',
            'created_at' => 'Yaratildi',
            'image' => 'Rasm',
            'file' => 'Rasm',
        ];
    }

    public static function getStatusList() {
        return ['off', 'on'];
    }

    public function getStatusName() {
        $list = self::getStatusList();
        return $list[$this->status_id];
    }

    public  function getAuthor() {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /*public function getBlogTag()
    {
        return $this->hasMany(BlogTag::className(), ['blog_id' => 'id']);
    }

    public function getTags()
    {
        return $this->hasMany(Tag::className(), ['id' => 'tag_id'])
            ->via('blogTag');
    }*/
    // quyida bu ikki metod o'rniga qisqartirilgan varianti keltirilgan(bitta metod yordamida)

    public function getTags()
    {
        return $this->hasMany(Tag::className(), ['id' => 'tag_id'])
            ->viaTable('blog_tag', ['blog_id' => 'id']);
    }

    public function getImageManager()
    {
        return $this->hasMany(ImageManager::className(), ['item_id' => 'id'])->andWhere(['class' => self::tableName()])->orderBy('sort');
    }

    public function getImagesLinks() {
        return ArrayHelper::getColumn($this->imageManager, 'imageName');
    }

    public function getImagesLinksData() {
        return ArrayHelper::toArray($this->imageManager, [
            ImageManager::className() => [
                'caption' => 'name',
                'key' => 'id',
            ]
        ]);
    }

    public function getTagsAsString() {
        $arr = ArrayHelper::map($this->tags,'id' , 'name');
        $str = implode(', ', $arr);
        return $str;
    }

    public function getSmallImage() {
        if($this->image) {
            $path = str_replace('admin.', '', Url::home(true)) . 'uploads/images/blog/50x50/' . $this->image;
        } else {
            $path = str_replace('admin.', '', Url::home(true)) . 'uploads/images/' . 'noimage.png';
        }
        return $path;
    }

    public function afterFind()
    {
        parent::afterFind();

        $this->_tags = $this->tags;
    }

    public function beforeSave($insert)
    {
        if($file = UploadedFile::getInstance($this, 'file')) {
            $dir = Yii::getAlias('@images').'/blog/';
          /*  echo $dir.$this->image;
            exit;*/
            if(is_file($dir . $this->image) && file_exists($dir . $this->image)) {
                unlink($dir . $this->image);
            }
            if(is_file($dir . $this->image) && file_exists($dir . '50x50/' . $this->image)) {
                unlink($dir . '50x50/' . $this->image);
            }
            if(is_file($dir . $this->image) && file_exists($dir . '800x/' . $this->image)) {
                unlink($dir . '800x/' . $this->image);
            }
            $this->image = strtotime('now') . '_' . Yii::$app->getSecurity()->generateRandomString(6) . '.' . $file->extension;
            $file->saveAs($dir.$this->image);
            $imag = Yii::$app->image->load($dir . $this->image);
            $imag->background('#fff', 0);
            $imag->resize('50', '50', Image::INVERSE);
            $imag->crop('50', '50');
            $imag->save($dir.'50x50/'.$this->image, 90);
            $imag = Yii::$app->image->load($dir . $this->image);
            $imag->background('#fff', 0);
            $imag->resize('800', NULL, Image::INVERSE);
            $imag->save($dir.'800x/'.$this->image, 90);
        }
        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $arr = ArrayHelper::map($this->tags, 'id', 'id');
        foreach ($this->_tags as $one) {
            if (!in_array($one, $arr)) {
                $model = new BlogTag();
                $model->blog_id = $this->id;
                $model->tag_id = $one;
                $model->save();
            }

            if (isset($arr[$one])) {
                unset($arr[$one]);
            }
        }
        BlogTag::deleteAll(['tag_id' => $arr]);
    }

    public function beforeDelete()
    {
        if(parent::beforeDelete()) {
            BlogTag::deleteAll(['blog_id' => $this->id]);
            return true;
        } else {
            return false;
        }

    }
}
