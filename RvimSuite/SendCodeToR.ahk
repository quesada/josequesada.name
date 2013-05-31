;
; AutoHotkey Version: 1.x
; Language:       English
; Platform:       Win9x/NT
; Author:         A.N.Other <myemail@nowhere.com>
;
; Script Function:
;	Template AutoHotkey script.
;

; ;;;;;;;;;;;;;;;;;;;;;;;
; ; vimTalkstoR
; ;;;;;;;;;;;;;;;;;;;;;;;
;
#WinActivateForce
F3::
WinGet vimWinID, ID, A           ; save current window ID to return here later
IfWinExist,R Console
{
	Send ^c                    ; copy selection to clipboard
	WinActivate ; ahk_class RGui
	WinGet WinRID, ID, A
	Send ^v
}

Sleep 30       					; wait until the data is pasted (normally not needed)
WinActivate ahk_id %vimWinID%    ; go back to the original window
;WinWaitActive   					; wait until original is active (normally not needed)
Return

