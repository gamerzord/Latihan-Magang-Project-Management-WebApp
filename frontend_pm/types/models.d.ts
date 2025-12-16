export interface User {
  id: number
  name: string
  email: string
  avatar_url?: string
  created_at: string
  updated_at: string
}

export interface Workspace {
  id: number
  name: string
  description?: string
  visibility: 'private' | 'workspace' | 'public'
  created_by: number
  creator?: User
  members?: readonly WorkspaceMember[]
  boards?: readonly Board[]
  boards_count?: number
  created_at: string
  updated_at: string
}

export interface WorkspaceMember {
  id: number
  name: string
  email: string
  avatar_url?: string
  created_at: string
  updated_at: string
  pivot: {
    workspace_id: number
    user_id: number
    role: 'owner' | 'admin' | 'member' | 'guest'
    invited_by?: number
    joined_at: string
    created_at: string
    updated_at: string
  }
}

export interface Board {
  id: number
  workspace_id: number
  title: string
  description?: string
  background_type: 'color' | 'image'
  background_value?: string
  visibility: 'private' | 'workspace' | 'public'
  created_by: number
  workspace?: Workspace
  creator?: User
  members?: readonly BoardMember[]
  lists?: readonly List[]
  labels?: readonly Label[]
  activities?: readonly Activity[]
  created_at: string
  updated_at: string
}

export interface BoardMember {
  id: number
  name: string
  email: string
  avatar_url?: string
  created_at: string
  updated_at: string
  pivot: {
    board_id: number
    user_id: number
    role: 'owner' | 'admin' | 'member' | 'guest'
    invited_by?: number
    joined_at: string
    created_at: string
    updated_at: string
  }
}

export interface CardMember {
  id: number
  name: string
  email: string
  avatar_url?: string
  created_at: string
  updated_at: string
  pivot: {
    card_id: number
    user_id: number
    assigned_by?: number
  }
}

export interface List {
  id: number
  board_id: number
  title: string
  position: number
  color?: string
  archived: boolean
  created_by: number
  board?: Board
  cards?: readonly Card[]
  creator?: User
  created_at: string
  updated_at: string
}

export interface Card {
  id: number
  list_id: number
  title: string
  description: string
  position: number
  due_date: string
  due_date_completed: boolean
  archived: boolean
  created_by: number
  list?: List
  creator?: User
  labels?: readonly Label[]
  members?: readonly User[]
  checklists?: readonly Checklist[]
  attachments?: readonly Attachment[]
  comments?: readonly Comment[]
  activities?: readonly Activity[]
  created_at: string
  updated_at: string
}

export interface Label {
  id: number
  board_id: number
  name: string
  color: string
  board?: Board
  cards?: readonly Card[]
  cards_count?: number
  created_at: string
  updated_at: string
}

export interface Checklist {
  id: number
  card_id: number
  title: string
  position: number
  card?: Card
  items?: readonly ChecklistItem[]
  progress?: {
    total: number
    completed: number
    percentage: number
  }
  created_at: string
  updated_at: string
}

export interface ChecklistItem {
  id: number
  checklist_id: number
  text: string
  position: number
  completed: boolean
  due_date?: string
  assigned_to?: number
  checklist?: Checklist
  assignee?: User
  created_at: string
  updated_at: string
}

export interface Attachment {
  id: number
  card_id: number
  type: 'file' | 'link'
  file_name: string
  file_url: string
  display_text?: string
  file_size?: number
  mime_type?: string
  uploaded_by: number
  card?: Card
  uploader?: User
  created_at: string
  updated_at: string
}

export interface Comment {
  id: number
  card_id: number
  user_id: number
  text: string
  edited_at?: string
  card?: Card
  user?: User
  created_at: string
  updated_at: string
}

export interface Activity {
  id: number
  user_id: number
  board_id?: number
  card_id?: number
  action_type: string
  action_data?: Record<string, any>
  user?: User
  board?: Board
  card?: Card
  created_at: string
  updated_at: string
}

export interface LoginRequest {
  email: string
  password: string
}

export interface RegisterRequest {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface AuthResponse {
  user: User
  token: string
}

export interface CreateWorkspaceRequest {
  name: string
  description?: string
  visibility: 'private' | 'workspace' | 'public'
}

export interface UpdateWorkspaceRequest {
  name?: string
  description?: string
  visibility?: 'private' | 'workspace' | 'public'
}

export interface AddWorkspaceMemberRequest {
  user_id: number
  role: 'member' | 'admin' | 'guest'
}

export interface UpdateMemberRoleRequest {
  role: 'member' | 'admin' | 'owner' | 'guest'
}

export interface CreateBoardRequest {
  workspace_id: number
  title: string
  description?: string
  background_type: 'color' | 'image'
  background_value?: string
  visibility: 'private' | 'workspace' | 'public'
}

export interface UpdateBoardRequest {
  title?: string
  description?: string
  background_type?: 'color' | 'image'
  background_value?: string
  visibility?: 'private' | 'workspace' | 'public'
  archived?: boolean
}

export interface CreateListRequest {
  board_id: number
  title: string
  color?: string
}

export interface UpdateListRequest {
  title?: string
  color?: string
  position?: number
  archived?: boolean
}

export interface CreateCardRequest {
  list_id: number
  title: string
  description?: string
  due_date?: string
}

export interface UpdateCardRequest {
  title?: string
  description?: string
  due_date?: string | null
  due_date_completed?: boolean
  archived?: boolean
  position?: number
  list_id?: number
}

export interface MoveCardRequest {
  list_id: number
  position: number
}

export interface ReorderListsRequest {
  lists: Array<{
    id: number
    position: number
  }>
}

export interface ReorderChecklistsRequest {
  checklists: Array<{
    id: number
    position: number
  }>
}

export interface ReorderChecklistItemsRequest {
  items: Array<{
    id: number
    position: number
  }>
}

export interface AddBoardMemberRequest {
  user_id: number
  role: 'member' | 'admin'
}

export interface AddCardMemberRequest {
  user_id: number
}

export interface AddLabelRequest {
  label_id: number
}

export interface CreateLabelRequest {
  board_id: number
  name?: string
  color: string
}

export interface UpdateLabelRequest {
  name?: string | null
  color?: string
}

export interface BulkUpdateLabelsRequest {
  labels: Array<{
    id?: number
    name?: string
    color: string
    action?: 'create' | 'update' | 'delete'
  }>
}

export interface CreateChecklistRequest {
  card_id: number
  title: string
}

export interface UpdateChecklistRequest {
  title?: string
  position?: number
}

export interface CreateChecklistItemRequest {
  checklist_id: number
  text: string
  due_date?: string
  assigned_to?: number
}

export interface UpdateChecklistItemRequest {
  text?: string
  completed?: boolean
  due_date?: string | null
  assigned_to?: number | null
  position?: number
}

export interface BulkUpdateChecklistItemsRequest {
  items: Array<{
    id?: number
    text?: string
    completed?: boolean
    due_date?: string
    assigned_to?: number
    position?: number
    action?: 'create' | 'update' | 'delete'
  }>
}

export interface CreateCommentRequest {
  card_id: number
  text: string
}

export interface UpdateCommentRequest {
  text: string
}

export interface CreateAttachmentRequest {
  card_id: number
  type: 'file' | 'link'
  file?: File
  url?: string
  name?: string
  display_text?: string
}

export interface UpdateAttachmentRequest {
  display_text?: string
  file_name?: string
}

export interface BulkDestroyAttachmentsRequest {
  attachment_ids: number[]
}

export interface CreateActivityRequest {
  board_id?: number
  card_id?: number
  action_type: string
  action_data?: Record<string, any>
}

export interface WorkspaceMembershipResponse {
  workspace: Workspace
  membership: {
    role: string
    joined_at: string
  }
}

export interface LabelUsageResponse extends Label {
  cards_count: number
}

export interface AttachmentStats {
  total: number
  files: number
  links: number
  total_size: number
}

export interface ActivityStats {
  total_activities: number
  activities_by_type: Record<string, number>
  activities_by_day: Array<{
    date: string
    count: number
  }>
  top_users: Array<{
    user: User
    activity_count: number
  }>
}

export interface CalendarEvent {
  id: number
  title: string
  start: string
  end?: string
  card: Card
  color?: string
  isOverdue: boolean
  isCompleted: boolean
}

export interface CalendarFilter {
  workspaceId?: number
  boardId?: number
  labelIds?: number[]
  memberIds?: number[]
  showCompleted?: boolean
  showOverdue?: boolean
  showDueToday?: boolean
  showOnlyMyCards?: boolean
  startDate?: string
  endDate?: string
}

interface BulkLabelResult {
  id: number
  action: 'created' | 'updated' | 'deleted'
  label?: Label
}


// API Response wrappers
export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface ApiResponse<T> {
  data?: T
  message?: string
  success: boolean
}