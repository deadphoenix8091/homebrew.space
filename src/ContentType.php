<?php

namespace HomebrewDB;

class ContentType
{
    const CIA = 0;
    const TDSX = 1;

    public function strToType($typeName) {
        if ($typeName == "cia" || $typeName == "CIA") {
            return self::CIA;
        }

        if ($typeName == "3dsx" || $typeName == "3DSX") {
            return self::TDSX;
        }
    }

    public function typeToStr($typeId) {
        if ($typeId == self::CIA) {
            return "CIA";
        }

        if ($typeId == self::TDSX) {
            return "3DSX";
        }
    }
}
