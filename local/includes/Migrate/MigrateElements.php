<?php




use Bitrix\Iblock\IblockTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CIBlockElement;
use CUtil;
use stringEncode\Exception;
use Throwable;

class MigrateElements{

    private string $fileName;
    private string $iBlockCode;
    private ?int $iBlockId = null;

    private array $errors = [];
    private array $success = [];

    const DEFAULTS = [
        'NAME'              => 'element',
        'IBLOCK_SECTION_ID' => false,
        'ACTIVE'            => 'Y',
        'PREVIEW_TEXT'      => '',
        'DETAIL_TEXT'       => '',
        'SORT' => 500,
    ];

    const REQ = [
        "NAME" => true,
        "PHONE" => true,
        "EMAIL" => true,
        "COORDS" => true,
        "CITY" => true,
        "CODE" => true,
    ];

    private array $elements = [];

    public function __construct($fileName, $iBlockCode)
    {
        $this->fileName = $fileName;
        $this->iBlockCode = $iBlockCode;
    }

    /**
     * @throws Exception
     * @throws LoaderException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws \JsonException
     */
    public function up()
    {
        try {
            $this->prepare();
            return [$this->iBlockCode, $this->iBlockId, $this->success];
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * @throws Exception
     * @throws LoaderException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws \JsonException
     */
    private function prepare()
    {

        $iBlockParams = $this->getIBlockData($this->iBlockCode);
        // Получаем данные по инфоблоку для получения ID инфоблока
        if(!count($iBlockParams)){
            throw new Exception('Нет данных по инфоблоку');
        }
        $this->iBlockId = $iBlockParams['ID'];

        $filePath = $this->getDocRoot() ."/local/includes/Migrate/files/{$this->fileName}.json";

        // Проверяем сущ. файл с данными
        if(!file_exists($filePath)){
            throw new Exception('Не верный путь до файла или файл не существует');
        }

        $raw = file_get_contents($filePath);
        try {
            // Проверяем на валидность json-а
            $this->elements = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        }catch (Throwable $e){
            throw new Exception('Ошибка в json файле');
        }

        // Исключаем сущ. элементы
        $this->checkExistsElements();

        if(!count($this->elements)){
            throw new Exception('Нет элементов для добавления');
        }

        try {
            // Добавляем элементы в инфоблок
            $this->addElement();
        }catch (Throwable $e){
            throw $e;
        }
    }


    public function addElement()
    {
        $iBlock = \Bitrix\Iblock\Iblock::wakeUp($this->iBlockId);

        foreach ($this->elements as $element){
            $fields = array_replace_recursive(self::DEFAULTS, $element);
            $element = $iBlock->getEntityDataClass()::createObject();

            $translitCode = $fields['CODE'] ?? Cutil::translit($fields['NAME'], 'ru');
            if ($element !== null) {
               $result =  $element->setName($fields['NAME'])
                    ->setEmail($fields['EMAIL'])
                    ->setCode($translitCode)
                    ->setPhone($fields['PHONE'])
                    ->setXmlId(uniqid())
                    ->setCoords($fields['COORDS'])
                    ->setCity($fields['CITY'])
                    ->save();

                if ( !$result->isSuccess() )
                {
                    $errors = $result->getErrors();
                    foreach ($errors as $error)
                    {
                        $this->errors[$error->getCode()] = $error->getMessage();
                    }
                }else{
                    $this->success[$element->getId()] = $element->getCode();
                }
            }
        }

        if(count($this->errors)){
            throw new \Exception(json_encode($this->errors));
        }
    }

/**
 */
public function addElementOld()
{
    foreach ($this->elements as $element) {
        $fields = array_replace_recursive(self::DEFAULTS, $element);
        $fields['IBLOCK_ID'] = $this->iBlockId;
        $ib = new CIBlockElement;
        $id = $ib->Add($fields);

        if ($id) {
            $this->success[$id] = $fields['NAME'];
        } else {
            $this->errors[$fields['NAME']] = $ib->LAST_ERROR;
        }
    }
}


    /**
     * @return string
     */
    public function getDocRoot(): string
    {
        $app = \Bitrix\Main\Application::getInstance();
        if ($app !== null) {
            return $app->getContext()->getServer()->getDocumentRoot();
        }

        return '';
    }

    /**
     * Создаём инфоблок с проверкой на существование
     * @param $iBlockCode
     * @return array
     * @throws LoaderException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getIBlockData($iBlockCode): array
    {
        Loader::includeModule('iblock');
        $parameters = [
            'filter' => [
                'CODE' => [
                    $iBlockCode
                ]
            ]
        ];
        $iBlockObject = IblockTable::getList($parameters)->fetchObject();
        if (empty($iBlockObject)) {
            return [];
        }
        return [
            'ID' => $iBlockObject->getId(),
            'CODE' => $iBlockObject->getCode()
        ];
    }


    /**
     * Простая проверка на ключи, не стал использовать
     */
    private function validateData()
    {
        foreach ($this->elements as $key => $element){
            if(array_diff_key(self::REQ, $element)) {
                unset($this->elements[$key]);
            }
        }
    }

    /**
     * @throws LoaderException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function checkExistsElements(): void
    {
        Loader::includeModule("iblock");

        if(!$this->elements){
            return;
        }

        $iBlock = \Bitrix\Iblock\Iblock::wakeUp($this->iBlockId);
        // Получаем элементы по координатам
        $elements = $iBlock->getEntityDataClass()::getList(
            [
                'filter' => [
                    'COORDS.VALUE' => array_column($this->elements, 'COORDS')
                ],
                'select' => ['ID', 'COORDS_' => 'COORDS']
            ]
        )->fetchAll();

        // Выходим если элементов в базе нет
        if(!$elements){
            return;
        }

        // Исключаем элементы которые уже есть в базе
        $existsCords = array_column($elements, 'COORDS_VALUE');
        foreach ($this->elements as $key => $element){
            if (in_array($element['COORDS'], $existsCords)) {
                unset($this->elements[$key]);
            }
        }
    }

}