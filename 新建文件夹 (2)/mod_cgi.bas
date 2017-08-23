Attribute VB_Name = "mod_cgi"
'mod_cgi.bas
'code by lichmama from cnblogs.com
'CGI支持状态
Public CGI_ENABLED As Boolean
'CGI程序目录
Public Const CGI_ROOT As String = "c:\cgi-bin\"

Private Declare Function CreatePipe Lib "kernel32" ( _
        phReadPipe As Long, _
        phWritePipe As Long, _
        lpPipeAttributes As SECURITY_ATTRIBUTES, _
        ByVal nSize As Long) As Long

Private Declare Sub GetStartupInfo Lib "kernel32" Alias "GetStartupInfoA" ( _
        lpStartupInfo As STARTUPINFO)

Private Declare Function CreateProcess Lib "kernel32" Alias "CreateProcessA" ( _
        ByVal lpApplicationName As String, _
        ByVal lpCommandLine As String, _
        lpProcessAttributes As Any, _
        lpThreadAttributes As Any, _
        ByVal bInheritHandles As Boolean, _
        ByVal dwCreationFlags As Long, _
        lpEnvironment As Any, _
        ByVal lpCurrentDriectory As String, _
        lpStartupInfo As STARTUPINFO, _
        lpProcessInformation As PROCESS_INFORMATION) As Long

Private Declare Function ReadFile Lib "kernel32" ( _
        ByVal hFile As Long, _
        lpBuffer As Any, _
        ByVal nNumberOfBytesToRead As Long, _
        lpNumberOfBytesRead As Long, _
        lpOverlapped As Any) As Long

Private Declare Function CloseHandle Lib "kernel32" ( _
        ByVal hObject As Long) As Long

Private Type SECURITY_ATTRIBUTES
    nLength As Long
    lpSecurityDescriptor As Long
    bInheritHandle As Long
End Type

Private Type PROCESS_INFORMATION
    hProcess As Long
    hThread As Long
    dwProcessId As Long
    dwThreadId As Long
End Type

Private Type STARTUPINFO
    cb As Long
    lpReserved As Long
    lpDesktop As Long
    lpTitle As Long
    dwX As Long
    dwY As Long
    dwXSize As Long
    dwYSize As Long
    dwXCountChars As Long
    dwYCountChars As Long
    dwFillAttribute As Long
    dwFlags As Long
    wShowWindow As Integer
    cbReserved2 As Integer
    lpReserved2 As Byte
    hStdInput As Long
    hStdOutput As Long
    hStdError As Long
End Type

Private Type OVERLAPPED
    ternal As Long
    ternalHigh As Long
    offset As Long
    OffsetHigh As Long
    hEvent As Long
End Type

Private Const STARTF_USESHOWWINDOW = &H1
Private Const STARTF_USESTDHANDLES = &H100
Private Const SW_HIDE = 0
Private Declare Sub RtlZeroMemory Lib "kernel32" (dest As Any, ByVal _
    numBytes As Long)


Public Function ShellCGI(ByVal head As Object, rep_state As Long) As String
    Dim sa As SECURITY_ATTRIBUTES
    Dim si As STARTUPINFO
    Dim pi As PROCESS_INFORMATION
    Dim hrp As Long
    Dim hwp As Long
    Dim ret As Long
    Dim envstr As String
    
    'fill this with CGI standard envrionment strings,
    '   which delimited by chr(0)
    envstr = MakeEnvString(head)
    Call RtlZeroMemory(ByVal VarPtr(sa), Len(sa))
    Call RtlZeroMemory(ByVal VarPtr(si), Len(si))
    Call RtlZeroMemory(ByVal VarPtr(pi), Len(pi))
    
    sa.nLength = Len(sa)
    sa.lpSecurityDescriptor = 0&
    sa.bInheritHandle = 1&
    
    'create pipe
    ret = CreatePipe(hrp, hwp, sa, 0&)
    If ret = 0 Then
        Debug.Print "[HTTP-VBS]: CGI Exception, pipe failed"
        Exit Function
    End If
    
    si.cb = Len(si)
    si.hStdOutput = hwp
    si.hStdError = hwp
    si.dwFlags = STARTF_USESHOWWINDOW Or STARTF_USESTDHANDLES
    si.wShowWindow = SW_HIDE
    
    'create the cgi-process, cgi-path: head("Path_Translated")
    ret = CreateProcess(head("Path_Translated"), vbNullString, _
        ByVal 0&, ByVal 0&, True, 0&, ByVal envstr, vbNullString, si, pi)
    If ret = 0 Then
        Debug.Print "[HTTP-VBS]: CGI Exception, create process failed"
        Exit Function
    End If
    
    'read response from cgi
    Dim nobr As Long 'num of bytes read
    Dim lpbuff As String
    Dim szbuff(65536 * 100) As Byte
    Dim sum As Long
    sum = 0
    Call RtlZeroMemory(ByVal VarPtr(szbuff(0)), 65536 * 100)
    Do
        nobr = 0&
        lpbuff = String(1024, " ")
        If ReadFile(hrp, ByVal lpbuff, 1024&, nobr, ByVal 0&) Then
            Call RtlMoveMemory(ByVal VarPtr(szbuff(sum)), ByVal StrPtr(lpbuff), LenB(lpbuff))
            sum = sum + LenB(lpbuff)
        Else
            Exit Do
        End If
        Call CloseHandle(hwp)
    Loop
    Call CloseHandle(hrp)
    
    rep_state = 200
    ShellCGI = Left(szbuff, sum)
End Function

Private Function MakeEnvString(ByVal head As Object) As String
    MakeEnvString = "REQUEST_METHOD=" & head("Request")("Method") & Chr(0) & _
        "CONTENT_TYPE=" & head("Content-Type") & Chr(0) & _
        "CONTENT_LENGTH=" & head("Content-Length") & Chr(0) & _
        "QUERY_STRING=" & head("Query_String") & Chr(0) & _
        "SCRIPT_NAME=" & head("Script_Name") & Chr(0) & _
        "PATH_INFO=" & head("Path_Info") & Chr(0) & _
        "PATH_TRANSLATED=" & head("Path_Translated") & Chr(0) & _
        "REMOTE_HOST=" & head("Remote_Host") & Chr(0) & _
        "REMOTE_ADDR=" & head("Remote_Addr") & Chr(0) & _
        "REMOTE_PORT=" & head("Remote_Port") & Chr(0) & _
        "REMOTE_USER=" & head("Remote_User") & Chr(0) & _
        "REMOTE_IDENT=" & head("Remote_Ident") & Chr(0) & _
        "AUTH_TYPE=" & head("Auth_Type") & Chr(0) & _
        "SERVER_NAME=http-vb/0.1" & Chr(0) & _
        "SERVER_PORT=80" & Chr(0) & _
        "SERVER_PROTOCOL=HTTP/1.1" & Chr(0) & _
        "DOCUMENT_ROOT=" & head("Document_Root") & Chr(0) & _
        "SERVER_SOFTWARE=http-vb/0.1 vb/6.0" & Chr(0) & _
        "HTTP_ACCEPT=" & head("Accept") & Chr(0) & _
        "HTTP_USER_AGENT=" & head("User-Agent") & Chr(0) & _
        "HTTP_REFERER=" & head("Referer") & Chr(0) & _
        "HTTP_COOKIE=" & head("Cookie") & Chr(0) & _
        "GATEWAY_INTERFACE=CGI/1.1" & Chr(0)
End Function
