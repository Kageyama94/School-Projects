open Graphics

(* ============================================================
   CONFIGURATION : fenetre, tailles, physique
   ============================================================ *)
let win_w = 1100
let win_h = 500
let cw = 6 (* largeur d'une case en pixels *)
let chp = 14 (* hauteur d'une case en pixels *)
let cwf = float_of_int cw
let chf = float_of_int chp
let bm = 40 (* marge basse en pixels *)
let fps = 60.0

(* dimensions du niveau et du perso *)
let pw = 3 (* largeur du perso en cases *)
let lc = 300 (* longueur du niveau en cases *)
let hc = 18 (* hauteur du niveau en cases *)

(* constantes physiques du mouvement *)
let gravity = 0.03
let jump = -0.35 (* saut normal (~3 cases) *)
let jump_rapide = -0.5 (* saut en mode course (~5 cases) *)
let run = 0.20 (* acceleration horizontale *)
let friction = 0.82 (* freinage au relachement *)
let vx_max = 0.6 (* vitesse horizontale max *)
let vy_max = 0.9 (* vitesse de chute max *)

(* ============================================================
   DONNEES ASCII : perso et ecrans
   ============================================================ *)
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

(* derive du perso et du chateau *)
let ph = Array.length character
let castle_larg = String.length castle.(0)
let castle_col0 = lc - castle_larg - 5
let zone_sure = castle_col0 - 5

(* ============================================================
   GENERATION DU NIVEAU
   ============================================================ *)
let make_trous () =
  let rec aux c acc =
    if c >= zone_sure then List.rev acc
    else
      let largeur = 3 + Random.int 3 in (* 3, 4 ou 5 *)
      let espace = 10 + Random.int 11 in (* sol plein entre deux trous : 10 a 20 *)
      let fin = c + largeur in
      aux (fin + espace) ((c, fin) :: acc)
  in
  aux 7 []

let trous = ref []
let est_trou c = List.exists (fun (d, f) -> c >= d && c < f) !trous

let generate_ground g =
  let sol = hc - 1 in
  let rec aux c =
    if c >= lc then ()
    else begin
      if not (est_trou c) then g.(sol).(c) <- '@';
      aux (c + 1)
    end
  in
  aux 0

let generate_obstacles g =
  let sol = hc - 1 in
  let rec aux c =
    if c >= zone_sure then ()
    else begin
      let hauteur = Random.int 6 in
      for h = 1 to hauteur do
        for j = 0 to 2 do (* 3 cases de large *)
          let col = c + j in
          if col < zone_sure && not (est_trou col) then
            g.(sol - h).(col) <- 'X'
        done
      done;
      aux (c + 40) (* prochain obstacle 40 cases plus loin *)
    end
  in
  aux 25

let generate_coins g =
  let sol = hc - 1 in
  let rec aux c =
    if c >= zone_sure then ()
    else begin
      if not (est_trou c) then begin
        let h = 1 + Random.int 6 in
        let r = sol - h in
        if r >= 0 && g.(r).(c) = ' ' then g.(r).(c) <- 'o'
      end;
      aux (c + 10)
    end
  in
  aux 20

let generate_castle g =
  let h = Array.length castle in
  let sol = hc - 1 in
  for i = 0 to h - 1 do
    for j = 0 to castle_larg - 1 do
      let ch = castle.(i).[j] in
      if ch <> ' ' then begin
        let r = sol - (h - i) in
        let c = castle_col0 + j in
        if r >= 0 && r < hc && c >= 0 && c < lc then g.(r).(c) <- ch
      end
    done
  done

let grille_init () =
  trous := make_trous ();
  let g = Array.make_matrix hc lc ' ' in
  generate_ground g;
  generate_obstacles g;
  generate_coins g;
  generate_castle g;
  g

(* ============================================================
   ETAT DU JEU
   ============================================================ *)
let grille = ref (grille_init ())
let px = ref 2.0
let py = ref (float_of_int (hc - 1 - ph))
let vx = ref 0.0
let vy = ref 0.0
let cam_x = ref 0.0
let score = ref 0
let gagne = ref false
let perdu = ref false
let facing = ref 1.0 (* 1.0 = droite, -1.0 = gauche *)
let tirs = ref [] (* projectiles : (x, y, dx) *)
let shoot_cd = ref 0 (* delai entre deux tirs *)
let mode_rapide = ref false (* course activee ? *)
let bascule_ok = ref true (* anti-repetition de la bascule course *)

let reset () =
  grille := grille_init ();
  px := 2.0;
  py := float_of_int (hc - 1 - ph);
  vx := 0.0; vy := 0.0; cam_x := 0.0;
  score := 0; gagne := false; perdu := false;
  tirs := []; shoot_cd := 0; facing := 1.0;
  mode_rapide := false; bascule_ok := true

(* ============================================================
   LOGIQUE : collisions, ramassage, victoire, mise a jour
   ============================================================ *)
let iround f = int_of_float (floor (f +. 0.5))

let solide r c =
  r >= 0 && r < hc && c >= 0 && c < lc &&
  (let g = !grille in
   (g.(r).(c) = '@' && r = hc - 1) || g.(r).(c) = 'X')

let collision_int x y =
  let h = ref false in
  for i = 0 to ph - 1 do
    for j = 0 to pw - 1 do
      if character.(i).[j] <> ' ' && solide (y + i) (x + j) then h := true
    done
  done;
  !h

let pour_chaque_case f =
  for i = 0 to ph - 1 do
    for j = 0 to pw - 1 do
      if character.(i).[j] <> ' ' then begin
        let r = iround !py + i and c = iround !px + j in
        if r >= 0 && r < hc && c >= 0 && c < lc then f r c
      end
    done
  done

let ramasser () =
  pour_chaque_case (fun r c ->
    if (!grille).(r).(c) = 'o' then begin
      (!grille).(r).(c) <- ' ';
      score := !score + 100
    end)

let verifier_victoire () =
  let porte_gauche  = castle_col0 + castle_larg / 2 - 3 in
  let porte_droite  = castle_col0 + castle_larg / 2 + 2 in
  let hg = iround !px and hd = iround !px + pw - 1 in
  if hg >= porte_gauche && hd <= porte_droite then gagne := true

let update go_left go_right do_jump run_fast do_shoot =
  let acc = if run_fast then run *. 1.7 else run in
  let vmax = if run_fast then vx_max *. 1.8 else vx_max in
  if go_left  then (vx := !vx -. acc; facing := -1.0);
  if go_right then (vx := !vx +. acc; facing := 1.0);
  if !vx > vmax then vx := vmax;
  if !vx < -.vmax then vx := -.vmax;
  px := !px +. !vx;
  if collision_int (iround !px) (iround !py) then begin
    if !vx > 0.0 then px := float_of_int (iround !px) -. 1.0
    else px := float_of_int (iround !px) +. 1.0;
    vx := 0.0
  end;
  if !px < 0.0 then (px := 0.0; vx := 0.0);
  if !px > float_of_int (lc - pw) then px := float_of_int (lc - pw);
  vx := !vx *. friction;

  let au_sol = collision_int (iround !px) (iround !py + 1) in
  if au_sol then begin
    if !vy > 0.0 then vy := 0.0;
    py := float_of_int (iround !py)
  end;

  let force_saut = if run_fast then jump_rapide else jump in
  if do_jump && au_sol then begin
    vy := force_saut;
    py := !py -. 1.0 (* decolle d'une case immediatement *)
  end;

  vy := !vy +. gravity;
  if !vy > vy_max then vy := vy_max;
  let oldy = !py in
  py := !py +. !vy;
  if collision_int (iround !px) (iround !py) then begin
    py := float_of_int (iround oldy);
    vy := 0.0
  end;

  if !py > float_of_int hc then perdu := true;

  if !shoot_cd > 0 then decr shoot_cd;
  if do_shoot && !shoot_cd = 0 then begin
    let depart_x = if !facing > 0.0 then !px +. float_of_int pw else !px -. 1.0 in
    tirs := (depart_x, !py +. 1.0, !facing *. 0.7) :: !tirs;
    shoot_cd := 5
  end;
  tirs := List.map (fun (x, y, dx) -> (x +. dx, y, dx)) !tirs;
  tirs := List.filter (fun (x, y, _) ->
    x >= 0.0 && x <= float_of_int lc && not (solide (iround y) (iround x))
  ) !tirs;

  ramasser ();
  verifier_victoire ();

  let visible_cols = float_of_int (win_w / cw) in
  let cible = !px -. visible_cols /. 3.0 in
  let cam_max = float_of_int lc -. visible_cols in
  let cible = max 0.0 (min (max 0.0 cam_max) cible) in
  cam_x := !cam_x +. (cible -. !cam_x) *. 0.15

(* ============================================================
   RENDU
   ============================================================ *)
let draw_glyph col_f row_f ch =
  let sx = int_of_float ((col_f -. !cam_x) *. cwf) in
  let sy = bm + int_of_float ((float_of_int (hc - 1) -. row_f) *. chf) in
  if sx > -cw && sx < win_w then begin
    moveto sx sy;
    draw_char ch
  end

let dessiner_carte () =
  let g = !grille in
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

let dessiner_tirs () =
  set_color (rgb 255 240 120);
  List.iter (fun (x, y, dx) ->
    draw_glyph x y (if dx > 0.0 then '>' else '<')
  ) !tirs

let dessiner_heros () =
  (* 1. effacer le decor derriere le perso (rectangle noir) *)
  set_color (rgb 0 0 0);
  for i = 0 to ph - 1 do
    for j = 0 to pw - 1 do
      let sx = int_of_float ((!px +. float_of_int j -. !cam_x) *. cwf) in
      let sy = bm + int_of_float ((float_of_int (hc - 1) -. (!py +. float_of_int i)) *. chf) in
      fill_rect sx sy cw chp
    done
  done;
  (* 2. dessiner le perso par-dessus *)
  for i = 0 to ph - 1 do
    for j = 0 to pw - 1 do
      let ch = character.(i).[j] in
      if ch <> ' ' then begin
        if i = 0 then set_color (rgb 230 60 40)
        else if ch = 'O' then set_color (rgb 250 220 170)
        else set_color (rgb 245 245 245);
        draw_glyph (!px +. float_of_int j) (!py +. float_of_int i) ch
      end
    done
  done

let dessiner_hud () =
  set_color (rgb 255 255 0);
  moveto 20 (win_h - 60); (* zone vide au-dessus du jeu *)
  draw_string (Printf.sprintf "SCORE : %d" !score)

let dessiner_message tableau couleur bas =
  let cw_px, ch_px = text_size "M" in
  let pas_v = ch_px + 2 in
  let nb_lignes = Array.length tableau in
  let larg = Array.fold_left (fun m s -> max m (String.length s)) 0 tableau in
  let x0 = (win_w - larg * cw_px) / 2 in
  let y_haut = (win_h + nb_lignes * pas_v) / 2 in
  set_color couleur;
  for i = 0 to nb_lignes - 1 do
    let ligne = tableau.(i) in
    for j = 0 to String.length ligne - 1 do
      if ligne.[j] <> ' ' then begin
        moveto (x0 + j * cw_px) (y_haut - i * pas_v);
        draw_char ligne.[j]
      end
    done
  done;
  set_color (rgb 235 235 235);
  let tw, _ = text_size bas in
  moveto ((win_w - tw) / 2) (y_haut - nb_lignes * pas_v - 20);
  draw_string bas

(* ============================================================
   BOUCLE PRINCIPALE
   ============================================================ *)
type etat = Jeu | Gagne | Perdu

let () =
  Random.self_init ();
  open_graph (Printf.sprintf " %dx%d" win_w win_h);
  set_window_title "Super Mario Bros - rendu texte";
  (try set_font "fixed" with _ -> ());
  auto_synchronize false;

  reset ();
  let etat = ref Jeu in
  let frame_time = 1.0 /. fps in
  let running = ref true in

  let t_left = ref 0 and t_right = ref 0 and t_jump = ref 0
  and t_shoot = ref 0 in
  let hold = 10 in
  let touches = [
    (['q'; 'Q'], t_left);
    (['d'; 'D'], t_right);
    (['z'; 'Z'], t_jump);
    (['f'; 'F'], t_shoot);
  ] in

  while !running do
    let t0 = Unix.gettimeofday () in

    let keys = ref [] in
    while key_pressed () do keys := read_key () :: !keys done;
    let pressed k = List.mem k !keys in
    if pressed '\027' then running := false;

    List.iter (fun (ks, t) ->
      if List.exists pressed ks then t := hold
    ) touches;

    if pressed 'a' || pressed 'A' then begin
      if !bascule_ok then begin
        mode_rapide := not !mode_rapide;
        bascule_ok := false
      end
    end else
      bascule_ok := true;

    (match !etat with
     | Gagne | Perdu ->
       if pressed '\r' || pressed '\n' then (reset (); etat := Jeu)
     | Jeu ->
       let go_left = !t_left > 0 in
       let go_right = !t_right > 0 in
       let do_jump = !t_jump > 0 in
       let do_shoot = !t_shoot > 0 in
       update go_left go_right do_jump !mode_rapide do_shoot;
       if !gagne then etat := Gagne;
       if !perdu then etat := Perdu);

    List.iter (fun (_, t) -> if !t > 0 then decr t) touches;

    set_color (rgb 0 0 0);
    fill_rect 0 0 (size_x ()) (size_y ());

    (match !etat with
     | Gagne -> dessiner_message win (rgb 250 205 70)
                  (Printf.sprintf "Score : %d   -   ENTREE pour rejouer" !score)
     | Perdu -> dessiner_message lose (rgb 225 70 45) "ENTREE pour recommencer"
     | Jeu ->
       dessiner_carte ();
       dessiner_tirs ();
       dessiner_heros ();
       dessiner_hud ());

    synchronize ();

    let dt = Unix.gettimeofday () -. t0 in
    if frame_time -. dt > 0.0 then Unix.sleepf (frame_time -. dt)
  done;
  close_graph ()