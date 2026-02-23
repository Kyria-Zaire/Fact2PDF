/** Palette de couleurs Fact2PDF (cohérente avec le web Bootstrap) */
export const Colors = {
  primary:    '#0d6efd',
  primaryDark:'#0a58ca',
  success:    '#198754',
  danger:     '#dc3545',
  warning:    '#ffc107',
  info:       '#0dcaf0',
  dark:       '#212529',
  gray:       '#6c757d',
  light:      '#f8f9fa',
  border:     '#dee2e6',
  white:      '#ffffff',
  black:      '#000000',

  // Statuts factures
  statusDraft:   '#6c757d',
  statusPending: '#ffc107',
  statusPaid:    '#198754',
  statusOverdue: '#dc3545',

  // Statuts projets
  projectTodo:       '#6c757d',
  projectInProgress: '#0d6efd',
  projectReview:     '#ffc107',
  projectDone:       '#198754',
  projectArchived:   '#adb5bd',

  // Priorités
  priorityLow:      '#adb5bd',
  priorityMedium:   '#0dcaf0',
  priorityHigh:     '#ffc107',
  priorityCritical: '#dc3545',
} as const;

export type ColorKey = keyof typeof Colors;
