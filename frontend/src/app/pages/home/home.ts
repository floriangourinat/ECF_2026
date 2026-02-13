import { AfterViewInit, Component, ElementRef, OnDestroy, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { HeaderComponent } from '../../components/header/header';
import { FooterComponent } from '../../components/footer/footer';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [CommonModule, RouterLink, HeaderComponent, FooterComponent],
  templateUrl: './home.html',
  styleUrl: './home.scss'
})
export class HomeComponent implements AfterViewInit, OnDestroy {
  @ViewChild('heroVideo')
  private heroVideo?: ElementRef<HTMLVideoElement>;

  private readonly onVisibilityChange = (): void => {
    if (document.visibilityState === 'visible') {
      this.startHeroVideo();
    }
  };

  ngAfterViewInit(): void {
    this.startHeroVideo();
    document.addEventListener('visibilitychange', this.onVisibilityChange);
  }

  ngOnDestroy(): void {
    document.removeEventListener('visibilitychange', this.onVisibilityChange);
  }

  private startHeroVideo(): void {
    const video = this.heroVideo?.nativeElement;
    if (!video) {
      return;
    }

    video.muted = true;
    video.defaultMuted = true;
    video.playsInline = true;

    const playPromise = video.play();
    if (playPromise) {
      void playPromise.catch(() => {
        // Ignore autoplay rejections: browser policy may delay until interaction.
      });
    }
  }
}
