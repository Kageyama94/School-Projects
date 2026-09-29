<?php

namespace Database\Seeders;

use App\Actions\CreateAccount;
use App\Enums\DayOfWeek;
use App\Enums\Level;
use App\Enums\RoomType;
use App\Enums\UserRole;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Licence;
use App\Models\Room;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Les matières et l'organisation par semestre (licences Informatique et Mathématiques) reproduisent
 * les maquettes officielles de l'UPEC (Université Paris-Est Créteil), reprises ici uniquement pour
 * peupler des données de démonstration réalistes.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const GENERICS_SUBJECTS = [
        // S1
        'Calculus 1', "Techniques d'expression / Epistémologie", 'Anglais scientifique 1',
        // S2
        'Projet professionnel et communication', 'Anglais scientifique 2',
        'Représentation des données', 'Architecture matérielle',
    ];

    private const INFORMATIQUE_SUBJECTS = [
        // S1
        'Programmation Java 1', 'Introduction aux bases de données', 'Création de pages web',
        'Environnement informatique',
        // S2
        'Calcul matriciel', 'Arithmétique modulaire', 'Programmation Java 2', 'Projet S2',
        "Introduction à l'assembleur",
        // S3
        "Analyse et probabilités pour l'informatique", 'Compression, correction, cryptographie',
        'Combinatoire et graphes', 'Programmation fonctionnelle', 'Programmation Java 3',
        'Projet S3', 'Réseaux', 'Anglais scientifique 3',
        // S4
        'Programmation C', 'Programmation Java 4', 'Programmation web',
        'Algorithmique et structures de données', 'Langages et automates', 'Projet S4',
        "Enseignement d'ouverture", 'Anglais scientifique 4',
        // S5
        'Grammaires algébriques', 'Introduction aux modèles de calcul', 'Programmation POSIX',
        'Programmation concurrente', 'Conception de bases de données',
        'Conception Javascript/UI/Frontend', 'Programmation Java 5', 'Projet S5',
        'Culture et insertion professionnelles', "Anglais de l'informatique 1",
        // S6
        'Culture des langages informatiques', 'Projet S6', 'Logique',
        'Algorithmes et complexité', 'Intelligence artificielle', "Anglais de l'informatique 2",
        'TEDS', 'Stage',
    ];

    private const MATHEMATIQUES_SUBJECTS = [
        // S1
        'Arithmétique et fondements', 'Mécanique du point 1', 'Electrocinétique', 'Bases Python',
        // S2
        'Analyse réelle', 'Algèbre linéaire', 'Mécanique du point 2', 'Optique géométrique',
    ];

    public function run(): void
    {
        // 3 salles standards + 1 gymnase + 1 salle informatique
        $rooms = Room::factory(3)->create(['type' => RoomType::Salle]);
        $rooms->push(Room::factory()->create(['type' => RoomType::Gymnase]));
        $rooms->push(Room::factory()->create(['type' => RoomType::Informatique]));

        $genericSubjects = $this->createSubjects(self::GENERICS_SUBJECTS);
        $informatiqueOwnSubjects = $this->createSubjects(self::INFORMATIQUE_SUBJECTS);
        $mathematiquesOwnSubjects = $this->createSubjects(self::MATHEMATIQUES_SUBJECTS);

        $informatiqueSubjects = $informatiqueOwnSubjects->merge($genericSubjects)->values();
        $mathematiquesSubjects = $mathematiquesOwnSubjects->merge($genericSubjects)->values();

        $informatiqueLicence = Licence::factory()->create(['name' => 'Informatique']);
        $mathematiquesLicence = Licence::factory()->create(['name' => 'Mathématiques']);

        // 2 groupes par niveau : L1 à L3 en Informatique (S1 à S6 de la maquette),
        // L1 seulement en Mathématiques (seule la maquette de L1 est disponible).
        $groups = collect([
            [$informatiqueLicence, Level::L1, 'Groupe A'],
            [$informatiqueLicence, Level::L1, 'Groupe B'],
            [$informatiqueLicence, Level::L2, 'Groupe A'],
            [$informatiqueLicence, Level::L2, 'Groupe B'],
            [$informatiqueLicence, Level::L3, 'Groupe A'],
            [$informatiqueLicence, Level::L3, 'Groupe B'],
            [$mathematiquesLicence, Level::L1, 'Groupe A'],
            [$mathematiquesLicence, Level::L1, 'Groupe B'],
        ])->map(fn ($group) => Group::factory()->create([
            'licence_id' => $group[0]->id,
            'level' => $group[1],
            'name' => $group[2],
        ]));

        // Chaque groupe n'est programmé que sur les matières réelles de sa propre licence.
        $subjectsByLicence = [
            $informatiqueLicence->id => $informatiqueSubjects,
            $mathematiquesLicence->id => $mathematiquesSubjects,
        ];

        // Un enseignant par matière (chaque cours de la maquette a un titulaire dédié) ; les
        // matières partagées entre les deux licences n'ont qu'un seul enseignant pour les deux.
        $teachers = $informatiqueOwnSubjects->merge($mathematiquesOwnSubjects)->merge($genericSubjects)->map(fn (Subject $subject) => tap(
            Teacher::factory()->create(),
            fn (Teacher $teacher) => $teacher->subjects()->attach($subject->id)
        ))->values();

        foreach ($teachers as $teacher) {
            $teacher->licences()->attach(collect($subjectsByLicence)
                ->filter(fn ($subjects) => $subjects->contains('id', $teacher->subjects->first()->id))
                ->keys());
        }

        foreach ($groups as $group) {
            Student::factory(5)->create(['group_id' => $group->id]);
        }

        $bookedTeachersPerSlot = [];
        $bookedRoomsPerSlot = [];

        foreach ($groups as $group) {
            $groupSubjects = $subjectsByLicence[$group->licence_id];

            foreach (DayOfWeek::schoolDays() as $schoolDay) {
                $day = $schoolDay->value;
                $daySlots = Lesson::slotsForDay($day);
                $pickCount = min(random_int(3, 4), count($daySlots));

                foreach (collect($daySlots)->random($pickCount) as $slot) {
                    $key = $day.'-'.$slot['start'];
                    $bookedTeachersPerSlot[$key] ??= [];
                    $bookedRoomsPerSlot[$key] ??= [];

                    $subject = $groupSubjects->random();

                    $availableTeachers = $teachers->filter(
                        fn ($teacher) => $teacher->subjects->contains('id', $subject->id)
                            && ! in_array($teacher->id, $bookedTeachersPerSlot[$key], true)
                    );

                    if ($availableTeachers->isEmpty()) {
                        continue;
                    }

                    $availableRooms = $rooms->reject(
                        fn ($room) => in_array($room->id, $bookedRoomsPerSlot[$key], true)
                    );

                    if ($availableRooms->isEmpty()) {
                        continue;
                    }

                    $teacher = $availableTeachers->random();
                    $bookedTeachersPerSlot[$key][] = $teacher->id;

                    $room = $availableRooms->random();
                    $bookedRoomsPerSlot[$key][] = $room->id;

                    Lesson::factory()->create([
                        'group_id' => $group->id,
                        'subject_id' => $subject->id,
                        'teacher_id' => $teacher->id,
                        'room_id' => $room->id,
                        'day_of_week' => $day,
                        'start_time' => $slot['start'],
                        'end_time' => $slot['end'],
                    ]);
                }
            }
        }

        // Comptes de démonstration (identifiant 1, 2, 3 — les comptes réels commencent à 94020000)
        User::factory()->create([
            'identifiant' => '1',
            'name' => 'Admin',
            'role' => UserRole::Admin,
            'password' => 'admin',
        ]);

        $demoTeacher = $teachers->first();
        $demoTeacher->update(['first_name' => 'Prof', 'last_name' => 'Demo']);
        $demoTeacher->user()->associate(User::factory()->create([
            'identifiant' => '2',
            'name' => 'Prof Demo',
            'role' => UserRole::Teacher,
            'password' => 'prof',
        ]));
        $demoTeacher->save();

        $demoGroup = $groups->first();
        $demoStudent = Student::factory()->create([
            'group_id' => $demoGroup->id,
            'first_name' => 'Eleve',
            'last_name' => 'Demo',
        ]);
        $demoStudent->user()->associate(User::factory()->create([
            'identifiant' => '3',
            'name' => 'Eleve Demo',
            'role' => UserRole::Student,
            'password' => 'eleve',
        ]));
        $demoStudent->save();

        $this->createMissingAccounts();
    }

    /**
     * Donne un compte à chaque enseignant et étudiant qui n'en a pas, comme depuis l'interface admin : identifiant
     * à partir de 94020000, mot de passe initial nom_prénom à changer à la première connexion. Enseignants et
     * étudiants sont pris dans un ordre aléatoire : le numéro ne révèle pas le rôle.
     */
    private function createMissingAccounts(): void
    {
        $createAccount = app(CreateAccount::class);

        $profiles = Teacher::whereNull('user_id')->get()
            ->map(fn (Teacher $teacher) => [UserRole::Teacher, $teacher])
            ->concat(Student::whereNull('user_id')->get()->map(fn (Student $student) => [UserRole::Student, $student]))
            ->shuffle();

        foreach ($profiles as [$role, $profile]) {
            $createAccount->handle(
                $role,
                $profile->only(['first_name', 'last_name']),
                fn (User $user) => $profile->update(['user_id' => $user->id]),
            );
        }
    }

    /**
     * @param  array<int, string>  $names
     * @return Collection<int, Subject>
     */
    private function createSubjects(array $names): Collection
    {
        return collect($names)->map(fn ($name) => Subject::factory()->create(['name' => $name]))->values();
    }
}
