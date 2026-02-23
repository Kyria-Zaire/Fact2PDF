/**
 * ProjectCard — compact card for project list
 */

import React, { memo } from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { ApiProject } from '@/services/api';
import { Colors }     from '@/constants/colors';

const STATUS_LABELS: Record<string, string> = {
  todo:        'À faire',
  in_progress: 'En cours',
  review:      'Révision',
  done:        'Terminé',
  archived:    'Archivé',
};

const PRIORITY_LABELS: Record<string, string> = {
  low:      'Basse',
  medium:   'Moyenne',
  high:     'Haute',
  critical: 'Critique',
};

interface Props {
  project: ApiProject;
  onPress?: (project: ApiProject) => void;
}

function ProjectCard({ project, onPress }: Props) {
  const statusMap   = Colors.projectStatus ?? {};
  const priorityMap = Colors.projectPriority ?? {};
  const statusColor   = (project?.status && statusMap[project.status as keyof typeof statusMap]) ?? Colors.textMuted;
  const priorityColor = (project?.priority && priorityMap[project.priority as keyof typeof priorityMap]) ?? Colors.textMuted;
  const isLate        = project?.is_late ?? false;

  return (
    <TouchableOpacity
      style={[styles.card, isLate && styles.cardLate]}
      onPress={() => onPress?.(project)}
      activeOpacity={onPress ? 0.75 : 1}
    >
      <View style={styles.header}>
        <Text style={styles.name} numberOfLines={2}>{project?.name ?? '—'}</Text>
        {isLate && (
          <View style={styles.lateBadge}>
            <Text style={styles.lateBadgeText}>Retard</Text>
          </View>
        )}
      </View>

      <View style={styles.badges}>
        <View style={[styles.badge, { backgroundColor: statusColor }]}>
          <Text style={styles.badgeText}>{STATUS_LABELS[project?.status ?? ''] ?? project?.status ?? '—'}</Text>
        </View>
        <View style={[styles.badge, { backgroundColor: priorityColor }]}>
          <Text style={styles.badgeText}>{PRIORITY_LABELS[project?.priority ?? ''] ?? project?.priority ?? '—'}</Text>
        </View>
      </View>

      {/* Progress bar */}
      <View style={styles.progressRow}>
        <View style={styles.progressTrack}>
          <View style={[styles.progressFill, { width: `${project?.progress ?? 0}%` as any, backgroundColor: statusColor }]} />
        </View>
        <Text style={styles.progressText}>{project?.progress ?? 0}%</Text>
      </View>

      {!!project?.end_date && (
        <Text style={[styles.date, isLate && styles.dateLate]}>
          Échéance : {new Date(project.end_date).toLocaleDateString('fr-FR')}
        </Text>
      )}
    </TouchableOpacity>
  );
}

export default memo(ProjectCard);

const styles = StyleSheet.create({
  card: {
    backgroundColor: Colors.white,
    borderRadius: 10,
    padding: 14,
    gap: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 3,
    elevation: 1,
  },
  cardLate: {
    borderLeftWidth: 3,
    borderLeftColor: Colors.danger,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    gap: 8,
  },
  name: {
    fontSize: 14,
    fontWeight: '600',
    color: Colors.textDark,
    flex: 1,
  },
  lateBadge: {
    backgroundColor: Colors.danger,
    borderRadius: 10,
    paddingHorizontal: 7,
    paddingVertical: 2,
  },
  lateBadgeText: {
    color: Colors.white,
    fontSize: 10,
    fontWeight: '700',
  },
  badges: {
    flexDirection: 'row',
    gap: 6,
  },
  badge: {
    borderRadius: 10,
    paddingHorizontal: 8,
    paddingVertical: 3,
  },
  badgeText: {
    fontSize: 11,
    color: Colors.white,
    fontWeight: '600',
  },
  progressRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  progressTrack: {
    flex: 1,
    height: 5,
    backgroundColor: Colors.border,
    borderRadius: 3,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    borderRadius: 3,
  },
  progressText: {
    fontSize: 11,
    color: Colors.textMuted,
    width: 32,
    textAlign: 'right',
  },
  date: {
    fontSize: 12,
    color: Colors.textMuted,
  },
  dateLate: {
    color: Colors.danger,
    fontWeight: '600',
  },
});
