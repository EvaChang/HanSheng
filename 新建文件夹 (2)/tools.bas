Attribute VB_Name = "tools"
'tools.bas
'code by lichmama from cnblogs.com
Public Declare Sub RtlMoveMemory Lib "kernel32" (Destination As Any, _
    Source As Any, _
    ByVal length As Long)
Private Declare Function GetTickCount Lib "kernel32" () As Long
Public Const WEB_ROOT As String = "c:\web"
Public req_types As Object
Public http_methods As Object

Public Function GetHeader(ByVal data As String, ByVal idex As Integer) As Object
'head [dictionary objet]:
'   Request,            [dictionary objet] <Method|File|Protocol>
'   Host,               [string]
'   Accept-Language,    [string]
'   *etc
'   Query_String,       [string]
    Set head = CreateObject("scripting.dictionary")
    Set rqst = CreateObject("scripting.dictionary")
    Call head.Add("Remote_Host", Form1.SckHandler(idex).RemoteHost)
    Call head.Add("Remote_Addr", Form1.SckHandler(idex).RemoteHostIP)
    Call head.Add("Remote_Port", Form1.SckHandler(idex).RemotePort)
    temp = Split(data, vbCrLf)
    'request's method, file and protocol
    rmfp = Split(temp(0), " ")
    If rmfp(0) = "POST" Then
        file = rmfp(1)
        Call head.Add("CGI_Access", True)
        Call head.Add("Query_String", unescape(temp(UBound(temp))))
        Call head.Add("Path_Info", file)
        Call head.Add("Script_Name", Replace(file, "/cgi-bin/", ""))
        Call head.Add("Path_Translated", Replace(file, "/cgi-bin/", CGI_ROOT))
    ElseIf rmfp(0) = "GET" Then
        'url like: /cgi-bin/test.exe?query=xxx
        If rmfp(1) Like "/cgi-bin/*.exe" Or rmfp(1) Like "/cgi-bin/*.exe?*" Then
            On Error Resume Next
            file = Split(rmfp(1), "?")(0)
            qrys = Split(rmfp(1), "?")(1)
            rmfp(1) = file
            Call head.Add("CGI_Access", True)
            Call head.Add("Query_String", unescape(qrys))
            Call head.Add("Path_Info", file)
            Call head.Add("Script_Name", Replace(file, "/cgi-bin/", ""))
            Call head.Add("Path_Translated", Replace(file, "/cgi-bin/", CGI_ROOT))
        End If
    End If
    Call rqst.Add("Method", rmfp(0))
    Call rqst.Add("File", rmfp(1))
    Call rqst.Add("Protocol", rmfp(2))
    Call head.Add("Request", rqst)
    For idex = 1 To UBound(temp)
        If temp(idex) = "" Then Exit For
        prop = Split(temp(idex), ": ")
        Call head.Add(prop(0), prop(1))
    Next
    Set GetHeader = head
End Function

Public Sub Sleep(ByVal dwDelay As Long)
    limt = GetTickCount() + dwDelay
    Do While GetTickCount < limt
        DoEvents
    Loop
End Sub

Function URLDecode(ByVal url As String) As String
'using the function [decodeURI] from js
    Set js = CreateObject("scriptcontrol")
    js.language = "javascript"
    URLDecode = js.eval("decodeURI('" & url & "')")
    Set js = Nothing
End Function

Public Function GetGMTDate() As String
    Dim WEEKDAYS
    Dim MONTHS
    
    WEEKDAYS = Array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat")
    MONTHS = Array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sept", "Oct", "Nov", "Dec")
    date_ = DateAdd("h", -8, Now())
    weekday_ = WEEKDAYS(Weekday(date_) - 1)
    month_ = MONTHS(Month(date_) - 1)
    day_ = Day(date_): year_ = Year(date_)
    time_ = Right(date_, 8)
    If Hour(time_) < 10 Then time_ = "0" & time_
    GetGMTDate = weekday_ & ", " & day_ & _
         " " & month_ & " " & year_ & _
         " " & time_ & " GMT"
End Function

Public Function Url2File(ByVal url As String) As String
    file = URLDecode(url)
'默认文件为 index.html
    If file = "/" Then file = "/index.html"
    file = Replace(file, "/", "\")
    file = WEB_ROOT & file
    Url2File = file
End Function

Public Function GetBytes(ByVal file As String, ByRef byts() As Byte) As Long
'not supported big file which size>2G
        fnum = FreeFile()
        Open file For Binary Access Read As #fnum
            size = LOF(fnum)
            If size = 0 Then
                byts = vbCrLf
            Else
                ReDim byts(size - 1) As Byte
                Get #fnum, , byts
            End If
        Close #fnum
        GetBytes = size
End Function

Public Function SetResponseHeader(ByVal size As Long) As String
'get the content-type from extension,
'   if file has not been defined, then set it to .*
    header = "HTTP/1.1 200 OK" & vbCrLf & _
            "Server: http-vb/0.1 vb/6.0" & vbCrLf & _
            "Date: " & GetGMTDate() & vbCrLf & _
            "Content-Type: " & ftype & vbCrLf & _
            "Content-Length: " & size & vbCrLf & vbCrLf
    SetResponseHeader = header
End Function

Public Sub sysLog(ByVal head As Object, ByVal status As String)
        Debug.Print "[HTTP-VB]: " & head("Request")("Method") & " " & _
            head("Request")("File") & " " & _
            head("Request")("Protocol") & " " & _
            head("Remote_Addr") & ":" & head("Remote_Port") & " " & _
            "-- " & status
End Sub

Public Function unescape(ByVal szescaped As String) As String
    Set js = CreateObject("scriptcontrol")
    js.language = "javascript"
    unescaped = js.eval("unescape(""" & szescaped & """)")
    Set js = Nothing
End Function

Public Sub StrCat(ByVal lpDest As Long, ByVal lpSrc As String, ByVal length As Long)
    Call RtlMoveMemory(lpDest, ByVal StrPtr(lpSrc), length)
End Sub
