<?php

////////////////////////////////////////////////////////////////////////////////

function antiMat($text) {
//global $text;

$noMat="��, ��������, ����������, ���� ���� ������ ����� �� ������...";

//������ ��������� ������� ������� ����

// "���"
$text = preg_replace("/\b[��Xx]+[\s_-]*[��Yy]+[\s_-]*[��]+\b/", "�����", $text);

// "����"
$text = preg_replace("/\b[��Ee]+[\s_-]*[��6]+[\s_-]*[��Aa]+[\s_-]*[��H]+\b/", "������ �������", $text);

// "����"
$text = preg_replace("/\b[��Cc]+[\s_-]*[��Yy]+[\s_-]*[��Kk]+[\s_-]*[��Aa]+\b/", "����� ����", $text);

// "����"
$text = preg_replace("/\b[��]+[\s_-]*[��Oo0]+[\s_-]*[��]+[\s_-]*[��Aa]+\b/", "�������", $text);

// "����"
$text = preg_replace("/\b[��]+[\s_-]*[��Oo0]+[\s_-]*[��]+[\s_-]*[��Yy]+\b/", "�������", $text);

// "�����"
$text = preg_replace("/\b[��]+[\s_-]*[��Uu]+[\s_-]*[��3]+[\s_-]*[��]+[\s_-]*[��Aa]+\b/", "�������", $text);

//"���"
$text = preg_replace("/\b[��6]+[\s_-]*[��]+[\s_-]*[��]+\b/", "��������", $text);

//"��"
$text = preg_replace("/\b[��Ee��]+[\s_-]*[��6]+\b/", "������", $text);

// "������"
$text = preg_replace("/\b[��]+[\s_-]*[��Uu]+[\s_-]*[��3]+[\s_-]*[��]+[\s_-]*[��Ee]+[\s_-]*[��]+\b/", "����� ��������", $text);


//����� ���������

//���� ����������� "����"
if (ereg("[��6]+[\s_-]*[��]+[\s_-]*[��]+[\s_-]*[��]+", $text)) return($noMat);

//���� ����������� "����"
if (ereg("[��]+[\s_-]*[��Uu]+[\s_-]*[��3]+[\s_-]*[��]+", $text)) return($noMat);

//���� ����������� "����|�|�|�"
if (ereg("[��Ee��]+[\s_-]*[��6]+[\s_-]*[��Aa]+[\s_-]*[��Tm��H����Pp]+", $text)) return($noMat);

//���� ����������� "����"
if (ereg("[��Ee��]+[\s_-]*[��6]+[\s_-]*[��H]+[\s_-]*[��Yy]+", $text)) return($noMat);

//���� ����������� "���|�|�|�|�"
if (ereg("[��Xx]+[\s_-]*[��Yy]+[\s_-]*[����Uu��Ee������]+", $text)) return($noMat);

//���� ����������� "���[��]�"
if (ereg("[��]+[\s_-]*[��Uu]+[\s_-]*[��]+[\s_-]*[��Aa��Oo0]+[\s_-]*[��Pp]+", $text)) return($noMat);


return ($text);

}
////////////////////////////////////////////////////////////////////////////////

function isMat($text) {

//���� ����������� "����"
if (ereg("[��6]+[_ ]*[��]+[_ ]*[��]+[_ ]*[��]+", $text)) return(true);

//���� ����������� "����"
if (ereg("[��]+[_ ]*[��Uu]+[_ ]*[��3]+[_ ]*[��]+", $text)) return(true);

//���� ����������� "����|�|�|�"
if (ereg("[��Ee��]+[_ ]*[��6]+[_ ]*[��Aa]+[_ ]*[��Tm��H����Pp]+", $text)) return(true);

//���� ����������� "����"
if (ereg("[��Ee��]+[_ ]*[��6]+[_ ]*[��H]+[_ ]*[��Yy]+", $text)) return(true);

//���� ����������� "���|�|�|�|�"
if (ereg("[��Xx]+[_ ]*[��Yy]+[_ ]*[����Uu��Ee������]+", $text)) return(true);

//���� ����������� "���[��]�"
if (ereg("[��]+[_ ]*[��Uu]+[_ ]*[��]+[_ ]*[��Aa��Oo0]+[_ ]*[��Pp]+", $text)) return(true);

return false;
}

?>