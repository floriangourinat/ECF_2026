import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { HeaderComponent } from '../../components/header/header';
import { FooterComponent } from '../../components/footer/footer';

@Component({
  selector: 'app-legal-notice',
  standalone: true,
  imports: [CommonModule, RouterLink, HeaderComponent, FooterComponent],
  templateUrl: './legal-notice.html',
  styleUrl: './legal-notice.scss'
})
export class LegalNoticeComponent {}