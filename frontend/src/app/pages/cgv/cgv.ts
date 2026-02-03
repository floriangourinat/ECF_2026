import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-cgv',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './cgv.html',
  styleUrl: './cgv.scss'
})
export class CgvComponent {}