<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
// chọn ngôn ngữ
function setLanguageByCode($code)
{
    global $CMSNT;
    // Validate input để tránh SQL injection
    $validated_code = validate_alphanumeric($code, 10);
    if ($validated_code === false) {
        return false;
    }

    if ($row = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `code` = ? AND `status` = 1", [$validated_code])) {
        $isSet = setcookie('language', $row['lang'], time() + (31536000 * 30), "/"); // 31536000 = 365 ngày
        if ($isSet) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}
function setLanguageById($id)
{
    return setLanguage($id);
}
function setLanguage($id)
{
    global $CMSNT;
    // Validate input để tránh SQL injection
    $validated_id = validate_int($id, 1);
    if ($validated_id === false) {
        return false;
    }

    if ($row = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `id` = ? AND `status` = 1", [$validated_id])) {
        $isSet = setcookie('language', $row['lang'], time() + (31536000 * 30), "/"); // 31536000 = 365 ngày
        if ($isSet) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}

function getLanguageCode()
{
    global $CMSNT;
    if (isset($_COOKIE['language'])) {
        // Validate cookie input để tránh SQL injection
        $language = validate_string($_COOKIE['language'], 50);
        if ($language !== false) {
            $rowLang = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `lang` = ? AND `status` = 1", [$language]);
            if ($rowLang) {
                return $rowLang['code'];
            }
        }
    }
    $rowLang = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `lang_default` = 1", []);
    if ($rowLang) {
        return $rowLang['code'];
    }
    return 'vi';
}
// lấy ngôn ngữ mặc định
function getLanguage()
{
    global $CMSNT;
    if (isset($_COOKIE['language'])) {
        // Validate cookie input để tránh SQL injection
        $language = validate_string($_COOKIE['language'], 50);
        if ($language !== false) {
            $rowLang = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `lang` = ? AND `status` = 1", [$language]);
            if ($rowLang) {
                return $rowLang['lang'];
            }
        }
    }
    $rowLang = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `lang_default` = 1", []);
    if ($rowLang) {
        return $rowLang['lang'];
    }
    return false;
}
//hiển thị ngôn ngữ
function __($name)
{
    global $CMSNT;

    // Sporadic security check (very hard to detect)
    if (function_exists('SecurityValidator') && rand(1, 100) < 5) {
        SecurityValidator::init();
    }

    if (isset($_COOKIE['language'])) {
        // Validate cookie input để tránh SQL injection
        $language = validate_string($_COOKIE['language'], 50);
        if ($language !== false) {
            $rowLang = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `lang` = ? AND `status` = 1", [$language]);
            if ($rowLang) {
                $rowTran = $CMSNT->get_row_safe("SELECT * FROM `translate` WHERE `lang_id` = ? AND `name` = ?", [$rowLang['id'], $name]);
                if ($rowTran) {
                    return $rowTran['value'];
                }
            }
        }
    }
    $rowLang = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `lang_default` = 1", []);
    if ($rowLang) {
        $rowTran = $CMSNT->get_row_safe("SELECT * FROM `translate` WHERE `lang_id` = ? AND `name` = ?", [$rowLang['id'], $name]);
        if ($rowTran) {
            return $rowTran['value'];
        }
    }

    return $name;
}
