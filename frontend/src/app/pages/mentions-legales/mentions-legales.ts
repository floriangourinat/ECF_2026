import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-mentions-legales',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './mentions-legales.html',
  styleUrl: './mentions-legales.scss'
})
export class MentionsLegalesComponent {}