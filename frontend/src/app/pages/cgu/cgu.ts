import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { HeaderComponent } from '../../components/header/header';
import { FooterComponent } from '../../components/footer/footer';

@Component({
  selector: 'app-cgu',
  standalone: true,
  imports: [CommonModule, RouterLink, HeaderComponent, FooterComponent],
  templateUrl: './cgu.html',
  styleUrl: './cgu.scss'
})
export class CguComponent {}