VERSION 5.00
Object = "{248DD890-BB45-11CF-9ABC-0080C7E7B78D}#1.0#0"; "mswinsck.ocx"
Object = "{648A5603-2C6E-101B-82B6-000000000014}#1.1#0"; "mscomm32.ocx"
Begin VB.Form Form1 
   BorderStyle     =   1  'Fixed Single
   Caption         =   "瀚盛--连接电子秤"
   ClientHeight    =   2835
   ClientLeft      =   45
   ClientTop       =   330
   ClientWidth     =   4425
   BeginProperty Font 
      Name            =   "微软雅黑"
      Size            =   8.25
      Charset         =   0
      Weight          =   400
      Underline       =   0   'False
      Italic          =   0   'False
      Strikethrough   =   0   'False
   EndProperty
   Icon            =   "Form1.frx":0000
   LinkTopic       =   "Form1"
   MaxButton       =   0   'False
   MinButton       =   0   'False
   ScaleHeight     =   2835
   ScaleWidth      =   4425
   StartUpPosition =   3  'Windows Default
   Begin VB.Timer Timer1 
      Enabled         =   0   'False
      Left            =   2280
      Top             =   2280
   End
   Begin MSCommLib.MSComm MSComm1 
      Left            =   360
      Top             =   2160
      _ExtentX        =   1005
      _ExtentY        =   1005
      _Version        =   393216
      DTREnable       =   -1  'True
   End
   Begin VB.ComboBox ports 
      BeginProperty Font 
         Name            =   "MS Sans Serif"
         Size            =   8.25
         Charset         =   0
         Weight          =   400
         Underline       =   0   'False
         Italic          =   0   'False
         Strikethrough   =   0   'False
      EndProperty
      Height          =   315
      Left            =   360
      TabIndex        =   3
      Text            =   "选择串口"
      Top             =   960
      Width           =   1815
   End
   Begin VB.TextBox weight 
      BeginProperty Font 
         Name            =   "微软雅黑"
         Size            =   12
         Charset         =   0
         Weight          =   400
         Underline       =   0   'False
         Italic          =   0   'False
         Strikethrough   =   0   'False
      EndProperty
      ForeColor       =   &H000000FF&
      Height          =   555
      Left            =   360
      TabIndex        =   2
      Top             =   1620
      Width           =   1815
   End
   Begin VB.CommandButton Command2 
      Caption         =   "最小化到托盘"
      BeginProperty Font 
         Name            =   "MS Sans Serif"
         Size            =   8.25
         Charset         =   0
         Weight          =   400
         Underline       =   0   'False
         Italic          =   0   'False
         Strikethrough   =   0   'False
      EndProperty
      Height          =   495
      Left            =   2640
      TabIndex        =   1
      Top             =   1800
      Width           =   1335
   End
   Begin VB.CommandButton Command1 
      Caption         =   "连接"
      BeginProperty Font 
         Name            =   "MS Sans Serif"
         Size            =   8.25
         Charset         =   0
         Weight          =   400
         Underline       =   0   'False
         Italic          =   0   'False
         Strikethrough   =   0   'False
      EndProperty
      Height          =   495
      Left            =   2640
      TabIndex        =   0
      Top             =   960
      Width           =   1335
   End
   Begin MSWinsockLib.Winsock SckHandler 
      Index           =   0
      Left            =   1680
      Top             =   2280
      _ExtentX        =   741
      _ExtentY        =   741
      _Version        =   393216
   End
   Begin MSWinsockLib.Winsock Winsock1 
      Left            =   1080
      Top             =   2280
      _ExtentX        =   741
      _ExtentY        =   741
      _Version        =   393216
   End
   Begin VB.Label Label2 
      AutoSize        =   -1  'True
      Caption         =   "iHanc--水产业专用销售系统"
      BeginProperty Font 
         Name            =   "微软雅黑"
         Size            =   14.25
         Charset         =   0
         Weight          =   400
         Underline       =   0   'False
         Italic          =   0   'False
         Strikethrough   =   0   'False
      EndProperty
      ForeColor       =   &H00C00000&
      Height          =   375
      Left            =   360
      TabIndex        =   5
      Top             =   120
      Width           =   3600
   End
   Begin VB.Label Label1 
      Caption         =   "请选择串口"
      BeginProperty Font 
         Name            =   "MS Sans Serif"
         Size            =   8.25
         Charset         =   0
         Weight          =   400
         Underline       =   0   'False
         Italic          =   0   'False
         Strikethrough   =   0   'False
      EndProperty
      Height          =   255
      Left            =   360
      TabIndex        =   4
      Top             =   600
      Width           =   975
   End
End
Attribute VB_Name = "Form1"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = False
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = False
'http-server vb made, version/0.1
'code by lichmama from cnblogs.com
'mail: kongdl@eastcom-sw.com
'@my dirty love
'
'winsock 状态常数， 这个是列出来给自己看的
Private Enum WINSOCK_STATE_ENUM
    sckClosed = 0               '关闭状态
    sckOpen = 1                 '打开状态
    sckListening = 2            '侦听状态
    sckConnectionPending = 3    '连接挂起
    sckResolvingHost = 4        '解析域名
    sckHostResolved = 5         '已识别主机
    sckConnecting = 6           '正在连接
    sckConnected = 7            '已连接
    sckClosing = 8              '同级人员正在关闭连接
    sckError = 9                '错误
End Enum
Dim myCheck As Boolean

Private Const page404 As String = "<!DOCTYPE html><html><head><title>404错误 - HTTP_VB(@lichmama)</title><body><br><p style='text-align:center;font-family:consolas'>""don't busy on trying, maybe you just took a wrong way of opening.""<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-- kindly tip from <i style='color:red;font-size:32px'>404</i></p></body></head></html>"
        
Private Sub Command1_Click()
If myCheck = True Then

    '启动监听
    Call Winsock1.Listen
    Me.Caption = "瀚盛--电子秤已连接"
    Debug.Print "[HTTP-VB]: startup http-server, listening on http://localhost:80"
    '开启串口
    Dim port
    port = Int(Right(ports.Text, 1))
    MSComm1.CommPort = port
    MSComm1.Settings = "2400,n,8,1"
    MSComm1.InputMode = comInputModeText
    MSComm1.InBufferCount = 0
    MSComm1.RThreshold = 5
    If MSComm1.PortOpen = False Then
    MSComm1.PortOpen = True
    Debug.Print "opening"
    Timer1.Enabled = True
    Timer1.Interval = 500
    Command1.Caption = "关闭"
    myCheck = False
    savePort (Right(ports.Text, 1))
    End If
    Else
     Call minWindow
     '关闭监听
    Call Winsock1.Close
    For i = 0 To 9
        Call SckHandler(i).Close
    Next
    Me.Caption = "瀚盛--电子秤未连接"
    Debug.Print "[HTTP-VB]: shutdown the http-server"
    Timer1.Enabled = False
    MSComm1.PortOpen = False
    Command1.Caption = "连接"
    weight.Text = "连接中..."
    myCheck = True
    
    End
    End If
    
End Sub

Private Sub Command2_Click()
 Call minWindow
End Sub



Private Sub Form_Load()
myCheck = True
'载入串口号
    Call getSerialPort
'当前支持的文件类型
    Set req_types = CreateObject("scripting.dictionary")
    Call req_types.Add(".html", "text/html")
    Call req_types.Add(".htm", "text/html")
    Call req_types.Add(".xml", "text/xml")
    Call req_types.Add(".js", "application/x-javascript")
    Call req_types.Add(".css", "text/css")
    Call req_types.Add(".txt", "text/plain")
    Call req_types.Add(".jpg", "image/jpeg")
    Call req_types.Add(".png", "image/image/png")
    Call req_types.Add(".gif", "image/image/gif")
    Call req_types.Add(".ico", "image/image/x-icon")
    Call req_types.Add(".bmp", "application/x-bmp")
    Call req_types.Add(".*", "application/octet-stream")
    
'http方法支持
    Set http_methods = CreateObject("scripting.dictionary")
    Call http_methods.Add("GET", True)
    Call http_methods.Add("POST", True)
    Call http_methods.Add("HEAD", False)
    Call http_methods.Add("PUT", False)
    Call http_methods.Add("DELETE", False)
    Call http_methods.Add("OPTIONS", False)

    For i = 1 To 9
        Call Load(SckHandler(i))
        With SckHandler(i)
            .Protocol = sckTCPProtocol
            .LocalPort = 80
            .Close
        End With
    Next
    
    With Winsock1
        .Protocol = sckTCPProtocol
        .Bind 80, "0.0.0.0"
        .Close
    End With
    
    '支持CGI
    CGI_ENABLED = True
End Sub

Private Sub Form_Unload(Cancel As Integer)
    Call Winsock1.Close
    For i = 0 To 9
        Call SckHandler(i).Close
    Next
End Sub

Private Sub mSerial_Click()

End Sub

Private Sub MSComm1_OnComm()
Dim BytReceived() As Byte
    Dim strBuff As String
    Dim message As String
    Dim i As Integer
    Debug.Print "OnComm"
    Select Case MSComm1.CommEvent    '事件发生
        Case comEvReceive
            Debug.Print "rvd"
            Cls
            MSComm1.InputLen = 0     '读入缓冲区全部内容
            strBuff = MSComm1.Input '读入到缓冲区
            message = StrReverse(strBuff)
            message = Mid(message, 1, 3) & "." & Mid(message, 4)
            
    End Select
    weight.Text = message
End Sub

Private Sub SckHandler_DataArrival(Index As Integer, ByVal bytesTotal As Long)
    Dim buff As String
    Call SckHandler(Index).GetData(buff, vbString, bytesTotal)
    Call Handle_Request(buff, Index)
End Sub

Private Sub SckHandler_SendComplete(Index As Integer)
    Call SckHandler(Index).Close
End Sub



Private Sub Timer1_Timer()
MSComm1.InBufferCount = 0
MSComm1.Output = "a"

End Sub

Private Sub Winsock1_ConnectionRequest(ByVal requestID As Long)
HANDLER_ENTRANCE_:
    For i = 0 To 9
        If SckHandler(i).State <> sckConnected And _
            SckHandler(i).State <> sckConnecting And _
            SckHandler(i).State <> sckClosing Then
            Call SckHandler(i).Accept(requestID)
            Exit Sub
        End If
    Next
    '如果未找到空闲的handler，等待100ms后，继续寻找
    Call Sleep(100): GoTo HANDLER_ENTRANCE_
End Sub

Private Sub Handle_Request(ByVal req As String, ByVal HandlerId As Integer)
    Dim byts() As Byte
    Dim mByts() As Byte
    Dim mStr As String
    
    Set head = GetHeader(req, HandlerId)
    
    If http_methods(head("Request")("Method")) <> True Then
        '方法不被支持
HANDLER_405__:
        SckHandler(HandlerId).SendData "HTTP/1.1 405 Method Not Allowed" & vbCrLf & _
            "Server: http-vb/0.1 vb/6.0" & vbCrLf & _
            "Date: " & GetGMTDate() & vbCrLf & _
            "Content-Length: 0" & vbCrLf & vbCrLf & vbCrLf
        Call sysLog(head, "405 Method Not Allowed")
        GoTo HANDLER_EXIT__
    End If
    
        mStr = "{" & Chr(34) & "weight" & Chr(34) & ":" & weight.Text & "}"
        mByts = StrConv(mStr, vbFromUnicode)
        size = UBound(mByts) + 1
        SckHandler(HandlerId).SendData SetResponseHeader(size)
        SckHandler(HandlerId).SendData mByts
        Erase byts
HANDLER_200__:
        Call sysLog(head, "200 OK")


HANDLER_EXIT__:
    Set head("Request") = Nothing
    Set head = Nothing
    Exit Sub
    
End Sub

Private Sub getSerialPort()
Dim i
For i = 1 To 16 Step 1
ports.AddItem "COM" & i
Next i
If Dir(App.Path & "\ports.txt") = "" Then
   ports.ListIndex = 0
Else
   Open App.Path & "\ports.txt" For Binary As #1
   s = Input(LOF(1), #1)
   i = Val(s) - 1
 Close #1
   ports.ListIndex = i
End If
End Sub

Private Sub minWindow()
With nfIconData

.hWnd = Me.hWnd

.uID = Me.Icon

.uFlags = NIF_ICON Or NIF_MESSAGE Or NIF_TIP

.uCallbackMessage = WM_MOUSEMOVE

.hIcon = Me.Icon.Handle

'定义鼠标移动到托盘上时显示的Tip

.szTip = App.Title + "(版本 " & App.Major & "." & App.Minor & "." & App.Revision & ")" & vbNullChar

.cbSize = Len(nfIconData)

End With

Call Shell_NotifyIcon(NIM_ADD, nfIconData)

'=============================================================System Tray End

Me.Hide
End Sub
Private Sub Form_QueryUnload(Cancel As Integer, UnloadMode As Integer)

Call Shell_NotifyIcon(NIM_DELETE, nfIconData)

End Sub
Private Sub Form_MouseMove(Button As Integer, Shift As Integer, X As Single, Y As Single)

Dim lMsg As Single

lMsg = X / Screen.TwipsPerPixelX

Select Case lMsg

Case WM_LBUTTONUP

'MsgBox "请用鼠标右键点击图标!", vbInformation, "实时播音专家"

'单击左键，显示窗体

ShowWindow Me.hWnd, SW_RESTORE

'下面两句的目的是把窗口显示在窗口最顶层

'Me.Show

'Me.SetFocus

'' Case WM_RBUTTONUP

'' PopupMenu MenuTray '如果是在系统Tray图标上点右键，则弹出菜单MenuTray '' Case WM_MOUSEMOVE

'' Case WM_LBUTTONDOWN

'' Case WM_LBUTTONDBLCLK

'' Case WM_RBUTTONDOWN

'' Case WM_RBUTTONDBLCLK

'' Case Else

End Select

End Sub

Private Sub savePort(portNumber As String)
'If Dir(App.Path & "\ports.txt") = "" Then
Open App.Path & "\ports.txt" For Output As #1
Print #1, portNumber
Close #1
'End If

End Sub
