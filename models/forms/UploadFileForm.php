<?php

namespace app\models\forms;

use app\components\Picture;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class UploadFileForm extends Model
{
    /**
     * @var UploadedFile
     */
    public $docFile;
    protected $docPath;
    protected $docName;

    const UPLOAD_DIR = 'web/docs';
    const VIEW_DIR = 'docs';

    public function rules()
    {
        return [
            ['docFile', 'file', 'maxSize' => (10 * 1024 * 1024), 'extensions' => 'pdf, doc, docx, xsl, xslx'],
        ];
    }

    public function upload()
    {
        if ($this->validate()) {
            $ext = $this->docFile->extension;

            $name = uniqid('doc_') . '.' . $ext;
            $this->docName = $name;
            $path = \Yii::$app->basePath . '/' . self::UPLOAD_DIR;
            $result_path = $path . '/' . $name;

            $this->docFile->saveAs($result_path);
            $this->docPath = '/' . self::VIEW_DIR . '/' . $name;
            return true;
        } else {
            return false;
        }
    }

    public function getDocPath($full_path = false) {
        $doc_path = $this->docPath;

        if ($full_path) $doc_path = Yii::$app->request->hostInfo . $doc_path;

        return $doc_path;
    }

    public function getDocName() {
        return $this->docName;
    }

    public static function getSrc($doc_name) {

        $src = [self::VIEW_DIR];
        $src[] = $doc_name;

       return '/' . implode('/', $src);
    }
}