<?php
require_once __DIR__ . '/admin_security.php';
function rae_db() {
    $serverName = "localhost\\SQLEXPRESS";
    $connectionOptions = ["Database"=>"21kEuroStar","Uid"=>"sa","PWD"=>"123456","CharacterSet"=>"UTF-8"];
    $conn = sqlsrv_connect($serverName,$connectionOptions);
    if (!$conn) throw new Exception("Database connection failed.");
    return $conn;
}
function rae_ensure_audit_table($conn): void {
    $sql = "IF OBJECT_ID(N'dbo.RAE_AdminAudit',N'U') IS NULL
    BEGIN
      CREATE TABLE dbo.RAE_AdminAudit(
        Id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        ActionType NVARCHAR(20) NOT NULL,
        ReportName NVARCHAR(50) NULL,
        TransactionLogId INT NULL,
        InventoryId INT NULL,
        AdminUser NVARCHAR(100) NOT NULL,
        IpAddress NVARCHAR(64) NULL,
        Reason NVARCHAR(1000) NOT NULL,
        BeforeData NVARCHAR(MAX) NULL,
        AfterData NVARCHAR(MAX) NULL,
        CreatedOn DATETIME2 NOT NULL DEFAULT SYSDATETIME()
      );
    END";
    if (sqlsrv_query($conn,$sql)===false) throw new Exception("Unable to initialize audit table.");
}
function rae_fetch_record($conn,int $id): ?array {
    $sql="SELECT TL.Id AS TransactionLogId,TL.InventoryId,TL.SourceDepartmentId,TL.DestinationDepartmentId,
      TL.Weight AS TransactionWeight,TL.Remark AS TransactionRemark,TL.[User] AS TransactionUser,
      TL.CreatedOn AS TransactionCreatedOn,I.ProductName,I.Remark AS InventoryRemark,I.Weight AS InventoryWeight,
      I.DepartmentId AS InventoryDepartmentId,I.CreatedOn AS InventoryCreatedOn
      FROM TransactionLogs TL INNER JOIN Inventories I ON I.Id=TL.InventoryId WHERE TL.Id=?";
    $st=sqlsrv_query($conn,$sql,[$id]); if($st===false) throw new Exception("Unable to load record.");
    $r=sqlsrv_fetch_array($st,SQLSRV_FETCH_ASSOC); return $r?:null;
}
function rae_clean_row(?array $row): ?array {
    if(!$row)return null; $o=[];
    foreach($row as $k=>$v) $o[$k]=($v instanceof DateTimeInterface)?$v->format('Y-m-d H:i:s'):$v;
    return $o;
}
function rae_audit($conn,string $action,string $report,?array $before,?array $after,int $tlId,int $invId,string $reason):void{
    rae_ensure_audit_table($conn);
    $sql="INSERT INTO RAE_AdminAudit(ActionType,ReportName,TransactionLogId,InventoryId,AdminUser,IpAddress,Reason,BeforeData,AfterData)
          VALUES(?,?,?,?,?,?,?,?,?)";
    $p=[$action,$report,$tlId,$invId,rae_audit_actor(),$_SERVER['REMOTE_ADDR']??'',$reason,
        json_encode(rae_clean_row($before),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        json_encode(rae_clean_row($after),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];
    if(sqlsrv_query($conn,$sql,$p)===false) throw new Exception("Audit record could not be saved.");
}
?>
