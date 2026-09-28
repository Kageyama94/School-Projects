// Grille d'emploi du temps de l'enseignant (composant Alpine du tableau de bord).
// `config` (créneaux, groupes, licences, salles, créneaux occupés) et `initial` viennent de la vue Blade.
function teacherScheduler(config, initial) {
    return {
        ...config,
        selectedLicenceId: initial.licence,
        selectedGroupId: initial.group,
        selectedDay: initial.day,
        selectedSlot: initial.slot,
        selectedRoomId: initial.room,
        groupName(groupId) {
            return this.groups.find(group => group.id === groupId)?.label ?? '';
        },
        groupsOfLicence() {
            return this.groups.filter(group => group.licence_id === this.selectedLicenceId);
        },
        selectLicence(licenceId) {
            this.selectedLicenceId = licenceId;
            this.selectedGroupId = this.groupsOfLicence()[0].id;
        },
        groupBusyAt(cellKey) {
            return (this.groupBusyCells[this.selectedGroupId] ?? []).includes(cellKey);
        },
        freeRooms() {
            const cellKey = this.selectedDay + '-' + this.slots[this.selectedSlot]?.start;

            return this.rooms.filter(room => ! (this.roomBusyCells[room.id] ?? []).includes(cellKey));
        },
        roomTooSmall(room) {
            const groupSize = this.groups.find(group => group.id === this.selectedGroupId)?.size ?? 0;

            return room.capacity !== null && room.capacity < groupSize;
        },
        pick(day, slotIndex) {
            this.selectedDay = day;
            this.selectedSlot = slotIndex;

            const chosen = this.freeRooms().find(room => String(room.id) === this.selectedRoomId);
            if (! chosen || this.roomTooSmall(chosen)) {
                this.selectedRoomId = '';
            }

            this.$dispatch('open-modal', 'add-lesson');
        },
    };
}
