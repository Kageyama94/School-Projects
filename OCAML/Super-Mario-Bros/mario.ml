open Graphics

(* ========== CONFIGURATION ========== *)
let win_w = 1100
let win_h = 500
let cw = ref 6
let chp = ref 14
let cwf = ref (float_of_int !cw)
let chf = ref (float_of_int !chp)
let bm = 40 (* marge basse en pixels *)
let fps = 60.0

(* ========== CLAVIER ========== *)
external raw_key_down : int -> bool = "caml_key_is_down"
external app_has_focus : unit -> bool = "caml_app_has_focus"
let vk_q = 0x51
let vk_d = 0x44
let vk_z = 0x5A
let vk_f = 0x46
let vk_shift = 0x10 (* maintenir pour courir *)
let vk_escape = 0x1B
let vk_return = 0x0D

let shoot_cooldown = 3 (* frames entre deux tirs quand la touche est tenue *)

let pw = 3 (* largeur du perso en cases *)
let lc = 300 (* longueur du niveau en cases *)
let hc = 18 (* hauteur du niveau en cases *)

let camera_bounds cell_w =
  let vc = float_of_int (win_w / cell_w) in
  (vc, float_of_int lc -. vc)

let visible_cols, cam_max =
  let vc, cm = camera_bounds !cw in
  (ref vc, ref cm)

let gravity = 0.03
let jump = -0.35 (* saut normal (~3 cases) *)
let jump_fast = -0.5 (* saut en mode course (~5 cases) *)
let run = 0.20 (* acceleration horizontale *)
let friction = 0.82 (* freinage au relachement *)
let vx_max = 0.6 (* vitesse horizontale max *)
let vy_max = 0.9 (* vitesse de chute max *)

(* ========== DONNEES ASCII ========== *)
let character = [|"{*}";
              "/O\\";
              "/ \\"|]

let castle = [|
"      |###          |###   ";
"      |             |      ";
"      |             |      ";
"     ###           ###     ";
"    #####         #####    ";
"   #######       #######   ";
"    #   #         #   #    ";
"    #####         #####    ";
"### ##### # # # # ##### ###";
" ######################### ";
" ############ ############ ";
" ###########   ########### ";
" ##########     ########## ";
" ##########     ########## ";
" ##########     ########## "
|]
let win = [|
"____       ____                      _____                _____ __                   ___";
"\\   \\     /   /                      \\    \\              /    /   |                 |   |";
" \\   \\   /   /_____   ___    ____     \\    \\     /\\     /    /'___'  __________     |   |";
"  \\   \\ /   /      \\ |   |   |   |     \\    \\   /  \\   /    /_______|          \\    |   |";
"   \\   Y   /   __   \\|   |   |   |      \\    \\ /    \\ /    /'_     _'    ___    '   |   |";
"    |     |   |  |   |   |   |   |       \\    Y      Y    /   |   | |   |   |   |   '___'";
"    |     |   '__'   |   '___'   |        \\              /    |   | |   |   |   |    ___";
"    |     |\\        /\\           |         \\     /\\     /    _'   '_|   |   |   |   |   |";
"    '__A__' \\______/  \\______,___'          \\___/  \\___/    '_______'___'   '___'   '___'";
|]
let lose = [|
"   _________                                                  _______                                         ___";
"  /         |                                                /       \\                                       |   |";
" /    ______'  _____ ___ ___ _____ ______      _______      /   ___   \\____       ____ ______  _________     |   |";
"|    /        /     `   Y   `     `       \\   /  ___  \\    |   |   |   \\   \\     /   /  ___  \\|   ___   \\    |   |";
"|   |    ___ /   ___    |    ___     ___   \\ /  (___)  \\   |   |   |   |\\   \\   /   /  (___)  \\  (___)   )   |   |";
"|   |   |   Y   (   |   |   |   |   |   |   Y          /   |   |   |   | \\   \\ /   /          /        _/    '___'";
"|   '___'   |   (___'   |   |   |   |   |   |    _____/    |   '___'   |  \\   Y   /|    _____/|   |\\   \\      ___";
" \\          A           |   |   |   |   |   |\\         \\    \\         /    \\     /  \\         \\   | \\   \\    |   |";
"  \\________/ \\______,___A___'   '___'   '___' \\________'     \\_______/      \\___/    \\________'___'  \\___\\   '___'";
|]

(* parcourt un art ASCII (tableau de string) : callback sur chaque case non-vide *)
let iter_ascii art f =
  for i = 0 to Array.length art - 1 do
    for j = 0 to String.length art.(i) - 1 do
      let ch = art.(i).[j] in
      if ch <> ' ' then f i j ch
    done
  done

let ph = Array.length character
let castle_width = String.length castle.(0)
let castle_col0 = lc - castle_width - 5
let safe_zone = castle_col0 - 5
let door_left = castle_col0 + castle_width / 2 - 3
let door_right = castle_col0 + castle_width / 2 + 2

(* ========== GENERATION DU NIVEAU ========== *)
let make_holes () =
  let rec aux c acc =
    if c >= safe_zone then List.rev acc
    else
      let width = 5 in
      let gap = 10 + Random.int 11 in (* sol plein entre deux trous : 10 a 20 *)
      let end_ = c + width in
      if end_ > safe_zone then List.rev acc (* ne pas deborder dans la zone sure *)
      else aux (end_ + gap) ((c, end_) :: acc)
  in
  aux 7 []

let is_hole holes c = List.exists (fun (d, f) -> c >= d && c < f) holes

let rec iter_step start stop step f =
  if start >= stop then ()
  else begin
    f start;
    iter_step (start + step) stop step f
  end

let generate_ground holes g =
  let ground = hc - 1 in
  iter_step 0 lc 1 (fun c ->
    if not (is_hole holes c) then g.(ground).(c) <- '@')

let generate_obstacles holes g =
  let ground = hc - 1 in
  iter_step 25 safe_zone 40 (fun c ->
    let height = Random.int 6 in
    for h = 1 to height do
      for j = 0 to 2 do (* 3 cases de large *)
        let col = c + j in
        if col < safe_zone && not (is_hole holes col) then
          g.(ground - h).(col) <- 'X'
      done
    done)

let generate_coins holes g =
  let ground = hc - 1 in
  let max_height = 5 + ph in
  iter_step 20 safe_zone 10 (fun c ->
    if not (is_hole holes c) then begin
      let h = 1 + Random.int max_height in
      let r = ground - h in
      if r >= 0 && g.(r).(c) = ' ' then g.(r).(c) <- 'o'
    end)

let generate_castle g =
  let h = Array.length castle in
  let ground = hc - 1 in
  iter_ascii castle (fun i j ch ->
    let r = ground - (h - i) in
    let c = castle_col0 + j in
    if r >= 0 && r < hc && c >= 0 && c < lc then g.(r).(c) <- ch)

let grid_init () =
  let holes = make_holes () in
  let g = Array.make_matrix hc lc ' ' in
  generate_ground holes g;
  generate_obstacles holes g;
  generate_coins holes g;
  generate_castle g;
  g

(* ========== ETAT DU JEU ========== *)
let grid = ref (grid_init ())
let px = ref 2.0
let py = ref (float_of_int (hc - 1 - ph))
let vx = ref 0.0
let vy = ref 0.0
let cam_x = ref 0.0
let score = ref 0
let facing = ref 1.0 (* 1.0 = droite, -1.0 = gauche *)
let shots = ref [] (* projectiles : (x, y, dx) *)
let shoot_cd = ref 0 (* delai entre deux shots *)

let reset () =
  grid := grid_init ();
  px := 2.0;
  py := float_of_int (hc - 1 - ph);
  vx := 0.0; vy := 0.0; cam_x := 0.0;
  score := 0;
  shots := []; shoot_cd := 0; facing := 1.0

(* ========== LOGIQUE ========== *)
let iround f = int_of_float (floor (f +. 0.5))
let clamp lo hi v = max lo (min hi v)

let is_solid r c =
  r >= 0 && r < hc && c >= 0 && c < lc &&
  (let ch = (!grid).(r).(c) in ch = '@' || ch = 'X')

let collision_at x y =
  let h = ref false in
  iter_ascii character (fun i j _ -> if is_solid (y + i) (x + j) then h := true);
  !h

let for_each_cell f =
  iter_ascii character (fun i j _ ->
    let r = iround !py + i and c = iround !px + j in
    if r >= 0 && r < hc && c >= 0 && c < lc then f r c)

let collect () =
  for_each_cell (fun r c ->
    if (!grid).(r).(c) = 'o' then begin
      (!grid).(r).(c) <- ' ';
      score := !score + 100
    end)

type state = Playing | Won | Lost

type input = { left : bool; right : bool; jump : bool; run : bool; shoot : bool }

let check_victory on_ground =
  let hg = iround !px and hd = iround !px + pw - 1 in
  on_ground && hg >= door_left && hd <= door_right

let update input =
  let acc, vmax, jump_force =
    if input.run then (run *. 1.7, vx_max *. 1.8, jump_fast)
    else (run, vx_max, jump)
  in
  if input.left  then (vx := !vx -. acc; facing := -1.0);
  if input.right then (vx := !vx +. acc; facing := 1.0);
  vx := clamp (-.vmax) vmax !vx;
  px := !px +. !vx;
  if collision_at (iround !px) (iround !py) then begin
    if !vx > 0.0 then px := float_of_int (iround !px) -. 1.0
    else px := float_of_int (iround !px) +. 1.0;
    vx := 0.0
  end;
  if !px < 0.0 then vx := 0.0;
  px := clamp 0.0 (float_of_int (lc - pw)) !px;
  vx := !vx *. friction;

  let on_ground = collision_at (iround !px) (iround !py + 1) in
  if on_ground then begin
    if !vy > 0.0 then vy := 0.0;
    py := float_of_int (iround !py)
  end;

  if input.jump && on_ground then begin
    vy := jump_force;
    let lift_y = !py -. 1.0 in
    if not (collision_at (iround !px) (iround lift_y)) then
      py := lift_y (* decolle d'une case immediatement, sauf plafond juste au-dessus *)
  end;

  vy := min vy_max (!vy +. gravity);
  let oldy = !py in
  py := !py +. !vy;
  if collision_at (iround !px) (iround !py) then begin
    py := float_of_int (iround oldy);
    vy := 0.0
  end;

  let lost = !py > float_of_int hc in

  if !shoot_cd > 0 then decr shoot_cd;
  if input.shoot && !shoot_cd = 0 then begin
    let start_x = if !facing > 0.0 then !px +. float_of_int pw else !px -. 1.0 in
    shots := (start_x, !py +. 1.0, !facing *. 0.7) :: !shots;
    shoot_cd := shoot_cooldown
  end;
  shots := List.filter_map (fun (x, y, dx) ->
    let x = x +. dx in
    if x >= 0.0 && x <= float_of_int lc && not (is_solid (iround y) (iround x))
    then Some (x, y, dx) else None
  ) !shots;

  collect ();
  let won = check_victory on_ground in

  let target = !px -. !visible_cols /. 3.0 in
  let target = clamp 0.0 (max 0.0 !cam_max) target in
  cam_x := !cam_x +. (target -. !cam_x) *. 0.15;

  if lost then Lost else if won then Won else Playing

(* ========== RENDU ========== *)
let screen_pos col_f row_f =
  let sx = int_of_float ((col_f -. !cam_x) *. !cwf) in
  let sy = bm + int_of_float ((float_of_int (hc - 1) -. row_f) *. !chf) in
  (sx, sy)

let draw_glyph col_f row_f ch =
  let sx, sy = screen_pos col_f row_f in
  if sx > -(!cw) && sx < win_w then begin
    moveto sx sy;
    draw_char ch
  end

let draw_map () =
  let g = !grid in
  for r = 0 to hc - 1 do
    for c = 0 to lc - 1 do
      let ch = g.(r).(c) in
      if ch <> ' ' then begin
        (match ch with
         | '@' -> set_color (rgb 70 200 70)
         | 'X' -> set_color (rgb 225 70 45)
         | 'o' -> set_color (rgb 250 220 60)
         | _ -> set_color white);
        draw_glyph (float_of_int c) (float_of_int r) ch
      end
    done
  done

let draw_shots () =
  set_color (rgb 255 240 120);
  List.iter (fun (x, y, dx) ->
    draw_glyph x y (if dx > 0.0 then '>' else '<')
  ) !shots

let draw_hero () =
  (* 1. effacer le decor derriere le perso (rectangle noir) *)
  set_color (rgb 0 0 0);
  for i = 0 to ph - 1 do
    for j = 0 to pw - 1 do
      let sx, sy = screen_pos (!px +. float_of_int j) (!py +. float_of_int i) in
      fill_rect sx sy !cw !chp
    done
  done;
  (* 2. dessiner le perso par-dessus *)
  iter_ascii character (fun i j ch ->
    if i = 0 then set_color (rgb 230 60 40)
    else if ch = 'O' then set_color (rgb 250 220 170)
    else set_color (rgb 245 245 245);
    draw_glyph (!px +. float_of_int j) (!py +. float_of_int i) ch)

let draw_hud () =
  set_color (rgb 255 255 0);
  moveto 20 (win_h - 60); (* zone vide au-dessus du jeu *)
  draw_string (Printf.sprintf "SCORE : %d" !score)

let draw_message art color bottom =
  let cw_px, ch_px = !cw, !chp in
  let line_step = ch_px + 2 in
  let n_lines = Array.length art in
  let width = Array.fold_left (fun m s -> max m (String.length s)) 0 art in
  let x0 = (win_w - width * cw_px) / 2 in
  let y_top = (win_h + n_lines * line_step) / 2 in
  set_color color;
  iter_ascii art (fun i j ch ->
    moveto (x0 + j * cw_px) (y_top - i * line_step);
    draw_char ch);
  set_color (rgb 235 235 235);
  let tw, _ = text_size bottom in
  moveto ((win_w - tw) / 2) (y_top - n_lines * line_step - 20);
  draw_string bottom

(* ========== BOUCLE PRINCIPALE ========== *)
let () =
  Random.self_init ();
  open_graph (Printf.sprintf " %dx%d" win_w win_h);
  set_window_title "Super Mario Bros";
  let is_monospace () =
    let w1, _ = text_size "M" in
    let w2, _ = text_size "i" in
    w1 > 0 && w1 = w2
  in
  let rec try_fonts = function
    | [] -> ()
    | f :: rest ->
      (try
         set_font f;
         if not (is_monospace ()) then try_fonts rest
       with _ -> try_fonts rest)
  in
  try_fonts ["fixed"; "Consolas"; "Lucida Console"; "Courier New"; "monospace"];

  let mw, mh = text_size "M" in
  if mw > 0 && mh > 0 then begin
    cw := mw; chp := mh;
    cwf := float_of_int mw; chf := float_of_int mh;
    let vc, cm = camera_bounds mw in
    visible_cols := vc; cam_max := cm
  end;

  auto_synchronize false;

  reset ();
  let state = ref Playing in
  let frame_time = 1.0 /. fps in
  let running = ref true in
  let prev_enter_key = ref false in

  while !running do
    let t0 = Unix.gettimeofday () in

    let focused = app_has_focus () in
    let key_down vk = focused && raw_key_down vk in

    if key_down vk_escape then running := false;

    let enter_key = key_down vk_return in
    (match !state with
     | Won | Lost ->
       if enter_key && not !prev_enter_key then (reset (); state := Playing)
     | Playing ->
       let input = {
         left = key_down vk_q;
         right = key_down vk_d;
         jump = key_down vk_z;
         run = key_down vk_shift;
         shoot = key_down vk_f;
       } in
       state := update input);
    prev_enter_key := enter_key;

    set_color (rgb 0 0 0);
    fill_rect 0 0 (size_x ()) (size_y ());

    (match !state with
     | Won -> draw_message win (rgb 250 205 70)
                  (Printf.sprintf "Score : %d   -   ENTREE pour rejouer" !score)
     | Lost -> draw_message lose (rgb 225 70 45) "ENTREE pour recommencer"
     | Playing ->
       draw_map ();
       draw_shots ();
       draw_hero ();
       draw_hud ());

    synchronize ();

    let dt = Unix.gettimeofday () -. t0 in
    if frame_time -. dt > 0.0 then Unix.sleepf (frame_time -. dt)
  done;
  close_graph ()