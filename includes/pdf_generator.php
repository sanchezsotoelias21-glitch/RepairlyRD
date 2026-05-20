<?php
class SimplePDF {

    private string $title = 'Reporte';
    private array $headers = [];
    private array $rows = [];

    public function setTitle(string $title): void {
        $this->title = $title;
    }

    public function setHeaders(array $headers): void {
        $this->headers = $headers;
    }

    public function setRows(array $rows): void {
        $this->rows = $rows;
    }

    public function output(): string {

        ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($this->title) ?></title>

<style>
body{
    font-family: Arial, sans-serif;
    padding:30px;
    color:#111827;
}

h1{
    margin-bottom:20px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#111827;
    color:white;
    padding:10px;
    text-align:left;
    font-size:13px;
}

td{
    border:1px solid #D1D5DB;
    padding:8px;
    font-size:12px;
}

tr:nth-child(even){
    background:#F9FAFB;
}
</style>
</head>

<body>

<h1><?= htmlspecialchars($this->title) ?></h1>

<table>
    <thead>
        <tr>
            <?php foreach($this->headers as $header): ?>
                <th><?= htmlspecialchars((string)$header) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>

    <tbody>
        <?php foreach($this->rows as $row): ?>
            <tr>
                <?php foreach($row as $cell): ?>
                    <td><?= htmlspecialchars((string)$cell) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
<?php
        return ob_get_clean();
    }
}
