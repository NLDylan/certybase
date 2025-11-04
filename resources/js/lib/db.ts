import Dexie, { type Table } from 'dexie';

export interface Project {
  id: string;
  name: string;
  template: string;
  createdAt: Date;
  updatedAt: Date;
  canvasData: any;
}

export class MySubClassedDexie extends Dexie {
  projects!: Table<Project>; 

  constructor() {
    super('certificateEditorDB');
    this.version(1).stores({
      projects: 'id, name, createdAt, updatedAt' // Primary key and indexed props
    });
  }
}

export const db = new MySubClassedDexie();
