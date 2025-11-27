<?php
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class minicart extends CModule
{
    public $MODULE_ID = "minicart";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . "/version.php";
        
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("MINICART_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("MINICART_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = Loc::getMessage("MINICART_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("MINICART_PARTNER_URI");
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();
    }

    public function DoUninstall()
    {
        $this->UnInstallFiles();
        $this->UnInstallEvents();
        $this->UnInstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function InstallDB()
    {
        $connection = Application::getConnection();

        if (!$connection->isTableExists('minicart_basket')) {
            $sql = "
                CREATE TABLE minicart_basket (
                    ID INT NOT NULL AUTO_INCREMENT,
                    FUSER_ID INT NOT NULL,
                    USER_ID INT NULL,
                    PRODUCT_ID INT NOT NULL,
                    QUANTITY DECIMAL(18,4) NOT NULL DEFAULT 1,
                    PRICE DECIMAL(18,2) NOT NULL,
                    CURRENCY VARCHAR(3) NOT NULL DEFAULT 'RUB',
                    DATE_INSERT DATETIME NOT NULL,
                    DATE_UPDATE DATETIME NOT NULL,
                    PROPERTIES TEXT NULL,
                    PRIMARY KEY (ID),
                    KEY IX_FUSER_ID (FUSER_ID),
                    KEY IX_USER_ID (USER_ID),
                    KEY IX_PRODUCT_ID (PRODUCT_ID)
                )
            ";
            $connection->query($sql);
        }

        if (!$connection->isTableExists('minicart_orders')) {
            $sql = "
                CREATE TABLE minicart_orders (
                    ID INT NOT NULL AUTO_INCREMENT,
                    USER_ID INT NOT NULL,
                    STATUS VARCHAR(50) NOT NULL DEFAULT 'NEW',
                    TOTAL_PRICE DECIMAL(18,2) NOT NULL,
                    CURRENCY VARCHAR(3) NOT NULL DEFAULT 'RUB',
                    CUSTOMER_NAME VARCHAR(255) NOT NULL,
                    CUSTOMER_PHONE VARCHAR(50) NOT NULL,
                    CUSTOMER_EMAIL VARCHAR(255) NULL,
                    CUSTOMER_ADDRESS TEXT NULL,
                    DATE_INSERT DATETIME NOT NULL,
                    DATE_UPDATE DATETIME NOT NULL,
                    PRIMARY KEY (ID),
                    KEY IX_USER_ID (USER_ID),
                    KEY IX_STATUS (STATUS)
                )
            ";
            $connection->query($sql);
        }

        if (!$connection->isTableExists('minicart_order_items')) {
            $sql = "
                CREATE TABLE minicart_order_items (
                    ID INT NOT NULL AUTO_INCREMENT,
                    ORDER_ID INT NOT NULL,
                    PRODUCT_ID INT NOT NULL,
                    PRODUCT_NAME VARCHAR(255) NOT NULL,
                    QUANTITY DECIMAL(18,4) NOT NULL,
                    PRICE DECIMAL(18,2) NOT NULL,
                    TOTAL_PRICE DECIMAL(18,2) NOT NULL,
                    PRIMARY KEY (ID),
                    KEY IX_ORDER_ID (ORDER_ID),
                    KEY IX_PRODUCT_ID (PRODUCT_ID)
                )
            ";
            $connection->query($sql);
        }
        
        return true;
    }

    public function UnInstallDB()
    {
        $connection = Application::getConnection();
        $connection->dropTable('minicart_basket');
        $connection->dropTable('minicart_orders');
        $connection->dropTable('minicart_order_items');
        return true;
    }

    public function InstallEvents()
    {
        return true;
    }

    public function UnInstallEvents()
    {
        return true;
    }

    public function InstallFiles()
    {
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/local/modules/minicart/install/components",
            $_SERVER["DOCUMENT_ROOT"]."/local/components",
            true, true
        );
        return true;
    }

    public function UnInstallFiles()
    {
        DeleteDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/local/modules/minicart/install/components",
            $_SERVER["DOCUMENT_ROOT"]."/local/components"
        );
        return true;
    }
}
?>
