<?php if (!defined("BASEPATH")) exit("No direct script access allowed");
if (!function_exists('ident_op')) {
    function ident_op($string)
    {
        $msg = '';
        $string = trim($string);
        $operator = ['=', '!=', '<', '>', '<=', '>=', '<>'];
        if ((substr($string, -1) == '%' && substr($string, 0, 1) == '%') || (substr($string, -1) == '%' || substr($string, 0, 1) == '%')) {
            $msg = "LIKE '" . $string . "'";
        } elseif (in_array(substr($string, 0, 2), $operator) || in_array(substr($string, 0, 1), $operator)) {
            if (in_array(substr($string, 0, 2), $operator)) {
                $msg = substr($string, 0, 2) . " '" . substr($string, 2) . "'";
            } elseif (in_array(substr($string, 0, 1), $operator)) {
                $msg = substr($string, 0, 1) . " '" . substr($string, 1) . "'";
            }
        } elseif (($string === 'IS NULL') || ($string === 'IS NOT NULL')) {
            $msg = $string;
        } else {
            $msg = "LIKE '%" . $string . "%'";
        }
        return $msg;
    }
}

if (!function_exists('buildWhereClause')) {
    function buildWhereClause($data)
    {
        $query = '';
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (!empty($value["value"])) {
                    $explodeVal = explode(',', $value["value"]);
                    $loop = 0;
                    $countSrc = count($explodeVal);
                    $key = (ctype_xdigit($value["column"])) ? str_replace('-', '.', hex2bin($value["column"])) : str_replace('-', '.', $value["column"]);
                    $key = str_replace("~", "-", $key);
                    if (@$value["type"] == "between") {
                        $exbtw = explode(" - ", $value["value"]);
                        if (!empty($exbtw[1])) {
                            $query .= $key . " BETWEEN '" . $exbtw[0] . "' AND '" . $exbtw[1] . "'";
                            $query .= ' AND ';
                        }
                    } else {
                        if (!empty($explodeVal[1])) {
                            foreach ($explodeVal as $key1 => $value1) {
                                if ($loop == 0) {
                                    $query .= '(';
                                    $query .= $key . ' ' . ident_op($value1);
                                } elseif ($countSrc == $loop + 1) {
                                    $query .= ' OR ';
                                    $query .= $key . ' ' . ident_op($value1);
                                    $query .= ')';
                                    $query .= ' AND ';
                                } else {
                                    $query .= ' OR ';
                                    $query .= $key . ' ' . ident_op($value1);
                                }
                                $loop++;
                            }
                        } else {
                            $query .= $key . ' ' . ident_op($value["value"]);
                            $query .= ' AND ';
                        }
                    }
                }
            }
        }
        if (substr(trim($query), -3) == 'AND') {
            $query = trim(substr($query, 0, -5));
        }

        return $query;
    }
}

function check_query($query)
{
    $msg = false;
    $CI = &get_instance();

    if ($CI->db->simple_query($query)) {
        $msg = [
            "response" => true
        ];
    } else {
        $msg = [
            "response" => false,
            "error" => [
                "code" => @$CI->db->error()["code"],
                "message" => @$CI->db->error()["message"]
            ]
        ];
    }

    return $msg;
}
