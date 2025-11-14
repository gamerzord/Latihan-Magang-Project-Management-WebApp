```
project-root/
├── nuxt.config.ts
├── package.json
├── tsconfig.json
├── app.vue
├── assets/
│   └── styles/
│       └── main.scss
├── components/
│   ├── board/
│   │   ├── BoardHeader.vue
│   │   ├── BoardSwitcher.vue
│   │   ├── BoardList.vue
│   │   └── BoardBackground.vue
│   ├── list/
│   │   ├── ListContainer.vue
│   │   ├── ListHeader.vue
│   │   ├── ListActions.vue
│   │   ├── ListColorPicker.vue
│   │   └── AddList.vue
│   ├── card/
│   │   ├── Card.vue
│   │   ├── CardModal.vue
│   │   ├── CardLabels.vue
│   │   ├── CardMembers.vue
│   │   ├── CardDueDate.vue
│   │   ├── CardDescription.vue
│   │   ├── CardAttachments.vue
│   │   ├── CardChecklist.vue
│   │   ├── CardComments.vue
│   │   └── AddCard.vue
│   ├── calendar/
│   │   ├── CalendarView.vue
│   │   ├── CalendarHeader.vue
│   │   ├── CalendarGrid.vue
│   │   ├── CalendarRangeSelector.vue
│   │   └── CalendarMenu.vue
│   ├── common/
│   │   ├── ColorPicker.vue
│   │   ├── DatePicker.vue
│   │   ├── MemberSelector.vue
│   │   ├── LabelManager.vue
│   │   └── AttachmentUploader.vue
│   └── layout/
│       ├── Navbar.vue
│       ├── Sidebar.vue
│       └── WorkspaceSelector.vue
├── composables/
│   ├── useBoard.ts
│   ├── useList.ts
│   ├── useCard.ts
│   ├── useCalendar.ts
│   ├── useLabels.ts
│   ├── useMembers.ts
│   ├── useDragDrop.ts
│   └── useWorkspace.ts
├── layouts/
│   └── default.vue
├── middleware/
│   └── auth.ts
├── pages/
│   └── index.vue
├── plugins/
│   └── vuetify.ts
├── stores/
│   ├── board.ts
│   ├── list.ts
│   ├── card.ts
│   ├── workspace.ts
│   ├── user.ts
│   └── ui.ts
├── types/
│   ├── board.ts
│   ├── list.ts
│   ├── card.ts
│   ├── workspace.ts
│   ├── user.ts
│   └── index.ts
└── utils/
    ├── api.ts
    ├── constants.ts
    ├── helpers.ts
    └── validators.ts
```

# ERD COMING SOON