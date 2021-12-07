REM ==============================================================================================
REM VBS starter
REM This script will run file.bat (%1) in invisible window.
REM ==============================================================================================

Set Fso = CreateObject( "Scripting.FileSystemObject" )
basename = Fso.GetBaseName( Wscript.ScriptFullName )
basepath = Fso.GetParentFolderName( Wscript.ScriptFullName )
REM cmd = basepath & "\" & basename & ".bat "

Dim Args()
ReDim Args( WScript.Arguments.Count - 1 )

For i = 0 To WScript.Arguments.Count - 1
   Args( i ) = """" & WScript.Arguments( i ) & """"
Next

cmd = cmd & Join( Args )

REM tmp = MsgBox("CMD: " & cmd , 64, "VBS starter")
REM WScript.Quit

REM Wait for [cmd] to finish to get exit code!
lvl = CreateObject( "WScript.Shell" ).Run( cmd, 0, true )

REM tmp = MsgBox( "Exit code: " & lvl , 48, "VBS starter" )
WScript.Quit( lvl )
