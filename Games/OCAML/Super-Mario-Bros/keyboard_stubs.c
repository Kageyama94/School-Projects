#include <caml/mlvalues.h>
#include <windows.h>

CAMLprim value caml_key_is_down(value vk) {
  SHORT state = GetAsyncKeyState(Int_val(vk));
  return Val_bool((state & 0x8000) != 0);
}

CAMLprim value caml_app_has_focus(value unit) {
  HWND fg = GetForegroundWindow();
  DWORD fg_pid;
  (void)unit;
  if (fg == NULL) return Val_bool(0);
  GetWindowThreadProcessId(fg, &fg_pid);
  return Val_bool(fg_pid == GetCurrentProcessId());
}
